<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FirestoreSeeder extends Seeder
{
    protected string $backupPath = 'D:\\Important Files\\HTML\\Systems\\SIPR\\firestore-backup.json';

    public function run(): void
    {
        $raw  = file_get_contents($this->backupPath);
        $data = json_decode($raw, true);

        if (!$data) {
            $this->command->error('Could not parse firestore-backup.json');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->seedMembers($data['members'] ?? []);
        $this->seedTransactions($data['transactions'] ?? []);
        $this->seedPayments();
        $this->seedProjects($data['investments'] ?? []);
        $this->seedNotices($data['announcements'] ?? []);
        $this->seedProposals($data['proposals'] ?? []);
        $this->seedActivityLog($data['activity'] ?? []);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Firestore data seeded successfully.');
    }

    // -------------------------------------------------------------------------
    // MEMBERS  (Firestore: members → MySQL: members)
    // -------------------------------------------------------------------------
    protected function seedMembers(array $docs): void
    {
        $this->command->info("Seeding members ({$this->count($docs)})…");
        $now = now();

        foreach ($docs as $doc) {
            $id = $doc['id'] ?? null;
            if (!$id) continue;

            $email = $doc['email'] ?? ($doc['gmail'] ?? null);
            if (!$email) {
                $this->command->warn("  Skipping member without email: {$id}");
                continue;
            }

            // Determine role
            $role = $this->mapRole($doc['role'] ?? 'member');

            // Registered-at: Firestore _registered bool or timestamp
            $registeredAt = null;
            if (!empty($doc['registeredAt'])) {
                $registeredAt = $this->tsToCarbon($doc['registeredAt']);
            }

            // Permissions JSON
            $permissions = null;
            if (!empty($doc['permissions']) && is_array($doc['permissions'])) {
                $permissions = json_encode($doc['permissions']);
            }

            $payload = [
                'id'             => $id,
                'name'           => $doc['name'] ?? 'Unknown',
                'email'          => $email,
                'phone'          => $doc['phone'] ?? null,
                'title'          => $doc['title'] ?? null,
                'role'           => $role,
                'locked'         => ($doc['locked'] ?? false) ? 1 : 0,
                'status'         => 'active',
                'google_uid'     => $doc['googleUid'] ?? null,
                'google_email'   => $doc['googleEmail'] ?? ($doc['gmail'] ?? null),
                'monthly_due'    => $doc['monthlyDue'] ?? 500.00,
                'password'       => Hash::make('sipr@2026'),
                'photo'          => $doc['photo'] ?? null,
                'gmail'          => $doc['gmail'] ?? null,
                'wa_link'        => $doc['waLink'] ?? null,
                'address'        => $doc['address'] ?? null,
                'emoji'          => $doc['emoji'] ?? null,
                'permissions'    => $permissions,
                'registered_at'  => $registeredAt,
                'source_payload' => json_encode($doc),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            DB::table('members')->upsert(
                [$payload],
                ['id'],
                array_keys(array_diff_key($payload, ['id' => 1]))
            );
        }
    }

    // -------------------------------------------------------------------------
    // TRANSACTIONS  (Firestore: transactions → MySQL: transactions)
    // -------------------------------------------------------------------------
    protected function seedTransactions(array $docs): void
    {
        $this->command->info("Seeding transactions ({$this->count($docs)})…");
        $now = now();

        // Build member-email → member-id map for lookups
        $memberMap = DB::table('members')
            ->select('id', 'email', 'name')
            ->get()
            ->keyBy('email');

        foreach ($docs as $doc) {
            $sourceId = $doc['id'] ?? null;

            // Resolve member_id
            $memberId = $doc['memberUID'] ?? null;
            if (!$memberId) {
                // Try to look up by email
                $memberEmail = $doc['memberEmail'] ?? null;
                if ($memberEmail && isset($memberMap[$memberEmail])) {
                    $memberId = $memberMap[$memberEmail]->id;
                }
            }

            if (!$memberId) {
                $this->command->warn("  Skipping transaction {$sourceId}: cannot resolve member_id");
                continue;
            }

            $type = $this->mapTransactionType($doc['type'] ?? 'deposit');
            $date = $doc['date'] ?? now()->toDateString();

            // payment_for_year / payment_for_month
            $payYear  = $doc['paymentForYear'] ?? null;
            $payMonth = $doc['paymentForMonth'] ?? null;

            DB::table('transactions')->upsert(
                [[
                    // source_id is unique, so upsert won't duplicate on re-seed
                    'source_id'        => $sourceId,
                    'member_id'        => $memberId,
                    'member_name'      => $doc['member'] ?? null,
                    'member_email'     => $doc['memberEmail'] ?? null,
                    'member_uid'       => $doc['memberUID'] ?? null,
                    'type'             => $type,
                    'amount'           => (float) ($doc['amount'] ?? 0),
                    'note'             => $doc['note'] ?? null,
                    'date'             => $date,
                    'paymentForYear'   => $payYear,
                    'paymentForMonth'  => $payMonth,
                    'source_payload'   => json_encode($doc),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]],
                ['source_id'],
                ['member_id', 'member_name', 'member_email', 'type', 'amount', 'note', 'date',
                 'paymentForYear', 'paymentForMonth', 'source_payload', 'updated_at']
            );
        }
    }

    // -------------------------------------------------------------------------
    // PAYMENTS  (derived from deposit transactions → MySQL: payments)
    // One payment record per member per month, amount = sum of deposits that month
    // -------------------------------------------------------------------------
    protected function seedPayments(): void
    {
        $this->command->info('Backfilling payments table from deposit transactions…');

        // Reset and rebuild from scratch for idempotency
        DB::table('payments')->truncate();

        $deposits = DB::table('transactions')
            ->where('type', 'deposit')
            ->orderBy('date')
            ->get();

        $buckets = [];  // [member_id][year][month] => ['amount'=>0,'date'=>'...']

        foreach ($deposits as $t) {
            $date  = new \DateTime($t->date);
            $month = $t->paymentForMonth ?? (int) $date->format('n');
            $year  = $t->paymentForYear  ?? (int) $date->format('Y');

            $key = "{$t->member_id}|{$year}|{$month}";
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'member_id' => $t->member_id,
                    'month'     => $month,
                    'year'      => $year,
                    'amount'    => 0,
                    'paid_at'   => $t->date,
                ];
            }
            $buckets[$key]['amount'] += (float) $t->amount;
        }

        $count = 0;
        foreach ($buckets as $row) {
            DB::table('payments')->insert([
                'member_id'   => $row['member_id'],
                'month'       => $row['month'],
                'year'        => $row['year'],
                'amount'      => $row['amount'],
                'paid_at'     => $row['paid_at'],
                'status'      => 'paid',
                'recorded_by' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $count++;
        }

        $this->command->info("  → {$count} payment records created.");
    }

    // -------------------------------------------------------------------------
    // PROJECTS  (Firestore: investments → MySQL: projects + project_milestones
    //                                         + project_collections)
    // -------------------------------------------------------------------------
    protected function seedProjects(array $docs): void
    {
        $this->command->info("Seeding projects/investments ({$this->count($docs)})…");
        $now = now();

        foreach ($docs as $doc) {
            $sourceId = $doc['id'] ?? null;

            // Map status — 'planning' is a valid enum value per the migration
            $status = match (strtolower($doc['status'] ?? 'active')) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'planning'  => 'planning',
                default     => 'active',
            };

            DB::table('projects')->upsert(
                [[
                    'source_id'      => $sourceId,
                    'name'           => $doc['name'] ?? 'Unnamed',
                    'description'    => $doc['description'] ?? null,
                    'type'           => $doc['sector'] ?? null,
                    'status'         => $status,
                    'capital'        => (float) ($doc['capitalDeployed'] ?? $doc['amount'] ?? 0),
                    'returned'       => (float) ($doc['actualReturn'] ?? 0),
                    'expected'       => (float) ($doc['expectedReturn'] ?? 0),
                    'team'           => (isset($doc['teamMembers']) && in_array('@all', (array)$doc['teamMembers'])) ? 'all' : 'custom',
                    'started_at'     => $doc['date'] ?? null,
                    'teamMembers'    => json_encode($doc['teamMembers'] ?? []),
                    'buyers'         => json_encode($doc['buyers'] ?? []),
                    'partner'        => $doc['partner'] ?? null,
                    'source_payload' => json_encode($doc),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]],
                ['source_id'],
                ['name', 'description', 'type', 'status', 'capital', 'returned', 'expected',
                 'team', 'started_at', 'teamMembers', 'buyers', 'partner',
                 'source_payload', 'updated_at']
            );

            // Fetch the project row id
            $project = DB::table('projects')->where('source_id', $sourceId)->first();
            if (!$project) continue;

            // Seed milestones
            foreach ((array) ($doc['milestones'] ?? []) as $ms) {
                if (empty($ms['title'])) continue;
                DB::table('project_milestones')->insert([
                    'project_id'     => $project->id,
                    'title'          => $ms['title'],
                    'note'           => $ms['note'] ?? null,      // added by expand migration
                    'achieved'       => ($ms['done'] ?? false) ? 1 : 0,
                    'achieved_at'    => ($ms['done'] ?? false) ? ($ms['date'] ?? null) : null,
                    'sort_order'     => 0,
                    'source_payload' => json_encode($ms),         // added by expand migration
                    'created_at'     => $now,
                ]);
            }

            // Seed collections (sales entries)
            foreach ((array) ($doc['collections'] ?? []) as $col) {
                $addedAt = null;
                if (!empty($col['addedAt'])) {
                    try { $addedAt = Carbon::parse($col['addedAt']); } catch (\Exception $e) {}
                }
                DB::table('project_collections')->insert([
                    'project_id'       => $project->id,
                    'collected_kg'     => (float) ($col['kg'] ?? 0),
                    'sold_kg'          => (float) ($col['soldKg'] ?? 0),
                    'revenue'          => (float) ($col['revenue'] ?? 0),
                    'note'             => $col['note'] ?? null,
                    'plastic_type'     => $col['plasticType'] ?? null,
                    'price_per_kg'     => (float) ($col['pricePerKg'] ?? 0),
                    'sale_note'        => $col['saleNote'] ?? null,
                    'source'           => $col['source'] ?? null,
                    'unit'             => $col['unit'] ?? null,
                    'recorded_by_name' => $col['addedBy'] ?? null,
                    'added_at'         => $addedAt,
                    'source_payload'   => json_encode($col),
                    'collected_at'     => $col['date'] ?? now()->toDateString(),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // NOTICES / ANNOUNCEMENTS  (Firestore: announcements → MySQL: notices)
    // -------------------------------------------------------------------------
    protected function seedNotices(array $docs): void
    {
        $this->command->info("Seeding announcements/notices ({$this->count($docs)})…");
        $now = now();

        foreach ($docs as $doc) {
            $sourceId = $doc['id'] ?? null;
            $createdAt = $this->tsToCarbon($doc['createdAt'] ?? null) ?? $now;

            DB::table('notices')->upsert(
                [[
                    'source_id'      => $sourceId,
                    'type'           => 'announcement',
                    'title'          => $doc['title'] ?? null,
                    'body'           => $doc['message'] ?? null,
                    'pinned'         => ($doc['pinned'] ?? false) ? 1 : 0,
                    'posted_by'      => null,  // author name only available, not ID
                    'source_payload' => json_encode($doc),
                    'created_at'     => $createdAt,
                    'updated_at'     => $now,
                ]],
                ['source_id'],
                ['title', 'body', 'pinned', 'source_payload', 'updated_at']
            );
        }
    }

    // -------------------------------------------------------------------------
    // PROPOSALS  (Firestore: proposals → MySQL: proposals)
    // -------------------------------------------------------------------------
    protected function seedProposals(array $docs): void
    {
        $this->command->info("Seeding proposals ({$this->count($docs)})…");
        $now = now();

        foreach ($docs as $doc) {
            $sourceId  = $doc['id'] ?? null;
            $createdAt = $this->tsToCarbon($doc['createdAt'] ?? null) ?? $now;

            // votes can be strings or arrays
            $votesYes = $this->normalizeVotes($doc['votesYes'] ?? null);
            $votesNo  = $this->normalizeVotes($doc['votesNo'] ?? null);

            // comments
            $comments = null;
            if (!empty($doc['comments'])) {
                $comments = is_array($doc['comments'])
                    ? json_encode($doc['comments'])
                    : json_encode([$doc['comments']]);
            }

            DB::table('proposals')->upsert(
                [[
                    'source_id'      => $sourceId,
                    'title'          => $doc['title'] ?? 'Untitled',
                    'description'    => $doc['description'] ?? '',
                    'amount'         => (float) ($doc['amount'] ?? 0),
                    'date'           => $doc['date'] ?? null,
                    'proposed_by'    => $doc['proposedBy'] ?? null,
                    'status'         => $doc['status'] ?? 'active',
                    'votes_yes'      => $votesYes,
                    'votes_no'       => $votesNo,
                    'comments'       => $comments,
                    'source_payload' => json_encode($doc),
                    'created_at'     => $createdAt,
                ]],
                ['source_id'],
                ['title', 'description', 'amount', 'date', 'proposed_by', 'status',
                 'votes_yes', 'votes_no', 'comments', 'source_payload']
            );
        }
    }

    // -------------------------------------------------------------------------
    // ACTIVITY LOG  (Firestore: activity → MySQL: activity_log)
    // -------------------------------------------------------------------------
    protected function seedActivityLog(array $docs): void
    {
        $this->command->info("Seeding activity log ({$this->count($docs)})…");

        foreach ($docs as $doc) {
            $sourceId = $doc['id'] ?? null;
            $iso = null;
            $ts  = null;

            if (!empty($doc['iso'])) {
                try { $iso = Carbon::parse($doc['iso']); } catch (\Exception $e) {}
            }
            if (!empty($doc['ts']['_seconds'])) {
                $ts = (int) $doc['ts']['_seconds'];
                if (!$iso) {
                    $iso = Carbon::createFromTimestamp($ts);
                }
            }

            DB::table('activity_log')->upsert(
                [[
                    'source_id'          => $sourceId,
                    'action'             => $doc['action'] ?? 'unknown',
                    'description'        => $doc['detail'] ?? null,
                    'performed_by'       => null, // only name/email available
                    'performed_by_name'  => $doc['by'] ?? null,
                    'performed_by_email' => $doc['byEmail'] ?? null,
                    'performed_by_role'  => $doc['role'] ?? null,
                    'iso'                => $iso,
                    'ts'                 => $ts,
                    'source_payload'     => json_encode($doc),
                    'created_at'         => $iso ?? now(),
                ]],
                ['source_id'],
                ['action', 'description', 'performed_by_name', 'performed_by_email',
                 'performed_by_role', 'iso', 'ts', 'source_payload', 'created_at']
            );
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function tsToCarbon(mixed $ts): ?Carbon
    {
        if (!$ts) return null;
        if (is_array($ts) && isset($ts['_seconds'])) {
            return Carbon::createFromTimestamp($ts['_seconds']);
        }
        if (is_string($ts)) {
            try { return Carbon::parse($ts); } catch (\Exception $e) {}
        }
        return null;
    }

    protected function mapRole(string $role): string
    {
        return match (strtolower($role)) {
            'admin'     => 'admin',
            'finance'   => 'finance',
            'secretary' => 'secretary',
            default     => 'member',
        };
    }

    protected function mapTransactionType(string $type): string
    {
        return match (strtolower($type)) {
            'deposit' => 'deposit',
            'profit'  => 'profit',
            'invest'  => 'invest',
            'expense' => 'expense',
            'fine'    => 'fine',
            default   => 'deposit',
        };
    }

    protected function normalizeVotes(mixed $votes): ?string
    {
        if (empty($votes)) return null;
        if (is_array($votes)) return json_encode($votes);
        // Could be a comma-separated string or single email
        $arr = array_filter(array_map('trim', explode(',', (string) $votes)));
        return json_encode(array_values($arr));
    }

    protected function count(array $arr): int
    {
        return count($arr);
    }
}
