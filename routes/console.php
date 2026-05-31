<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\FirestoreBackupImportService;
use App\Services\SqlFileImportService;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('sipr:import-firestore {path?}', function () {
    $path = $this->argument('path') ?: base_path('firestore-backup.json');

    $stats = app(FirestoreBackupImportService::class)->import($path);

    $this->info(sprintf(
        'Imported members=%d, transactions=%d, projects=%d, proposals=%d, notices=%d, activity=%d',
        $stats['members'],
        $stats['transactions'],
        $stats['projects'],
        $stats['proposals'],
        $stats['notices'],
        $stats['activity'],
    ));
})->purpose('Import the Firestore backup JSON into SQL tables');

Artisan::command('sipr:import-sql {path?}', function () {
    $path = $this->argument('path') ?: database_path('firestore-backup.sql');

    $result = app(SqlFileImportService::class)->import($path);

    $this->info(sprintf(
        'Imported SQL from %s (%d statements)',
        $result['path'],
        $result['statements'],
    ));
})->purpose('Import a SQL file (e.g. database/firestore-backup.sql)');

Artisan::command('sipr:reseed-backup {path?}', function () {
    $path = $this->argument('path') ?: database_path('firestore-backup.sql');

    $this->comment("Truncating tables and importing backup from {$path}");

    $tables = [
        'transactions', 'expenses', 'project_collections', 'project_milestones',
        'projects', 'proposals', 'notices', 'activity_log', 'members',
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $table) {
        try {
            DB::table($table)->truncate();
        } catch (\Throwable $e) {
            $this->comment("Skipping truncate for {$table}: {$e->getMessage()}");
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    $result = app(SqlFileImportService::class)->import($path);

    $this->info(sprintf('Re-seeded from %s (%d statements)', $result['path'], $result['statements']));
})->purpose('Clear key tables and reload the backup SQL');

Artisan::command('sipr:verify-backup {path?}', function () {
    $path = $this->argument('path') ?: database_path('firestore-backup.sql');

    if (!file_exists($path)) {
        $this->error("Backup SQL not found at {$path}");
        return;
    }

    $sql = file_get_contents($path);

    $tablesToCheck = [
        'members', 'transactions', 'expenses', 'projects', 'proposals', 'notices', 'activity_log',
    ];

    $sqlCounts = array_fill_keys($tablesToCheck, 0);

    // Helper to split rows inside a VALUES(...) list (copied logic)
    $splitInsertRows = function (string $values): array {
        $rows = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];
            $previous = $index > 0 ? $values[$index - 1] : null;

            if ($char === "'" && $previous !== '\\') {
                $inString = ! $inString;
            }

            if (! $inString) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                }
            }

            $buffer .= $char;

            if ($depth === 0 && ! $inString && $char === ')') {
                $rows[] = trim($buffer);
                $buffer = '';

                while ($index + 1 < $length && ctype_space($values[$index + 1])) {
                    $index++;
                }

                if ($index + 1 < $length && $values[$index + 1] === ',') {
                    $index++;
                }

                while ($index + 1 < $length && ctype_space($values[$index + 1])) {
                    $index++;
                }
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $rows[] = $tail;
        }

        return $rows;
    };

    // Find all INSERT statements and count rows per table
    if (preg_match_all('/INSERT\s+INTO\s+`?([^`\s]+)`?\s*\((.*?)\)\s*VALUES\s*(.*?);/is', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $table = $m[1];
            $values = trim($m[3]);

            if (in_array($table, $tablesToCheck, true)) {
                $rows = $splitInsertRows($values);
                $sqlCounts[$table] += count($rows);
            }
        }
    }

    $this->info('Verification report:');

    // Special-case members: compare unique emails instead of raw row count
    $sqlMemberEmails = [];
    if (preg_match('/INSERT INTO `members` \([^)]+\) VALUES\s*(.*);/is', $sql, $m)) {
        $vals = $m[1];
        if (preg_match_all("/\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'/", $vals, $mems, PREG_SET_ORDER)) {
            foreach ($mems as $r) {
                $email = strtolower(trim($r[3]));
                if ($email !== '') $sqlMemberEmails[] = $email;
            }
        }
    }

    $sqlUniqueMemberCount = count(array_unique($sqlMemberEmails));

    foreach ($tablesToCheck as $table) {
        $dbCount = 0;

        try {
            $dbCount = DB::table($table)->count();
        } catch (\Throwable $e) {
            $this->comment("Table {$table} not accessible: {$e->getMessage()}");
            continue;
        }
        if ($table === 'members') {
            $this->line(sprintf("- %s: SQL(unique emails)=%d  DB=%d %s", $table, $sqlUniqueMemberCount, $dbCount, ($sqlUniqueMemberCount === $dbCount) ? '[OK]' : '[MISMATCH]'));
        } else {
            $this->line(sprintf("- %s: SQL=%d  DB=%d %s", $table, $sqlCounts[$table] ?? 0, $dbCount, ($sqlCounts[$table] === $dbCount) ? '[OK]' : '[MISMATCH]'));
        }
    }

    // Summary suggestions
    $mismatches = [];

    foreach ($tablesToCheck as $t) {
        try {
            $dbCount = DB::table($t)->count();
        } catch (\Throwable $e) {
            continue;
        }

        if ($t === 'members') {
            if ($sqlUniqueMemberCount !== $dbCount) {
                $mismatches[] = $t;
            }
        } else {
            if (($sqlCounts[$t] ?? 0) !== $dbCount) {
                $mismatches[] = $t;
            }
        }
    }

    if (count($mismatches) === 0) {
        $this->info('All table counts match the SQL backup.');
    } else {
        $this->warn('Mismatches detected for: '.implode(', ', $mismatches));
        $this->warn('If you want to align the DB with the backup, run `php artisan sipr:reseed-backup`.');
    }
})->purpose('Compare backup SQL row counts with current DB (non-destructive)');

Artisan::command('sipr:reconcile-members {--apply}', function () {
    $apply = (bool) $this->option('apply');

    $this->info($apply ? 'Running member reconciliation (apply changes)...' : 'Dry-run member reconciliation (no changes)');

    $members = App\Models\Member::all();

    // Build index by identifiers
    $index = [];

    foreach ($members as $m) {
        $keys = [];
        if ($m->email) $keys[] = 'email:'.strtolower(trim($m->email));
        if ($m->google_email) $keys[] = 'google_email:'.strtolower(trim($m->google_email));
        if ($m->gmail) $keys[] = 'gmail:'.strtolower(trim($m->gmail));
        if ($m->wa_link) $keys[] = 'wa:'.trim($m->wa_link);
        if ($m->phone) $keys[] = 'phone:'.preg_replace('/\D+/', '', $m->phone);

        foreach (array_unique($keys) as $key) {
            $index[$key][] = $m->id;
        }
    }

    // Find groups with more than 1 member
    $groups = [];
    foreach ($index as $key => $ids) {
        $ids = array_values(array_unique($ids));
        if (count($ids) > 1) {
            sort($ids);
            $groups[implode('|', $ids)] = $ids;
        }
    }

    // Normalize groups
    $uniqueGroups = [];
    foreach ($groups as $k => $ids) {
        $uniqueGroups[implode('|', $ids)] = $ids;
    }

    if (empty($uniqueGroups)) {
        $this->info('No potential duplicate members detected.');
        return;
    }

    $this->line('Detected duplicate member groups:');

    $plan = [];

    foreach ($uniqueGroups as $ids) {
        $rows = App\Models\Member::whereIn('id', $ids)->get();

        // choose canonical: prefer earliest created_at, then non-empty email
        $canonical = $rows->sortBy('created_at')->first();

        $others = $rows->where('id', '!=', $canonical->id)->values();

        $plan[] = [
            'canonical' => $canonical->toArray(),
            'duplicates' => $others->toArray(),
        ];

        $this->line(sprintf("- Canonical: %s (%s) — Duplicates: %d", $canonical->id, $canonical->email ?? $canonical->name, count($others)));
    }

    if (! $apply) {
        $this->info('Dry-run complete. Run with --apply to perform the merges.');
        return;
    }

    // Apply plan
    DB::transaction(function () use ($plan) {
        foreach ($plan as $group) {
            $canon = $group['canonical']['id'];
            foreach ($group['duplicates'] as $dup) {
                $dupId = $dup['id'];

                // Reassign transactions
                App\Models\Transaction::where('member_id', $dupId)->update(['member_id' => $canon, 'member_uid' => $canon]);

                // Reassign expenses recorded_by
                try {
                    DB::table('expenses')->where('recorded_by', $dupId)->update(['recorded_by' => $canon]);
                } catch (\Throwable $e) {
                    // ignore if table not present or column missing
                }

                // Delete duplicate member
                App\Models\Member::where('id', $dupId)->delete();
                $this->line("Merged and removed {$dupId} -> {$canon}");
            }
        }
    });

    $this->info('Member reconciliation applied.');
})->purpose('Detect and optionally merge duplicate member rows (use --apply to commit)');
