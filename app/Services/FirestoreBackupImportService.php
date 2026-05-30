<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Notice;
use App\Models\Project;
use App\Models\ProjectCollection;
use App\Models\ProjectMilestone;
use App\Models\Proposal;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FirestoreBackupImportService
{
    public function import(string $path): array
    {
        $path = $this->resolvePath($path);
        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return DB::transaction(function () use ($payload) {
            return [
                'members' => $this->importMembers($payload['members'] ?? []),
                'transactions' => $this->importTransactions($payload['transactions'] ?? []),
                'projects' => $this->importProjects($payload['investments'] ?? []),
                'proposals' => $this->importProposals($payload['proposals'] ?? []),
                'notices' => $this->importNotices($payload['announcements'] ?? []),
                'activity' => $this->importActivity($payload['activity'] ?? []),
            ];
        });
    }

    private function resolvePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        $relativePath = base_path(ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));

        if (File::exists($relativePath)) {
            return $relativePath;
        }

        throw new \InvalidArgumentException("Firestore backup not found at {$path}");
    }

    private function importMembers(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $memberId = (string) ($record['id'] ?? $this->generateMemberId());
            $member = $this->findImportedMember($record, $memberId);

            $memberData = [
                'name' => $record['name'] ?? 'Member',
                'email' => $record['email'] ?? ($record['googleEmail'] ?? $record['gmail'] ?? $memberId.'@sipr.local'),
                'phone' => $record['phone'] ?? null,
                'title' => $record['title'] ?? null,
                'role' => $record['role'] ?? 'member',
                'locked' => (bool) ($record['locked'] ?? false),
                'status' => $record['status'] ?? 'active',
                'google_uid' => $record['googleUid'] ?? null,
                'google_email' => $record['googleEmail'] ?? null,
                'photo' => $record['photo'] ?? null,
                'gmail' => $record['gmail'] ?? null,
                'wa_link' => $record['waLink'] ?? null,
                'address' => $record['address'] ?? null,
                'emoji' => $record['emoji'] ?? null,
                'permissions' => $record['permissions'] ?? null,
                'registered_at' => $this->parseDateTime($record['_registered'] ?? null),
                'restored_at' => $this->parseDateTime($record['restoredAt'] ?? null),
                'source_payload' => $record,
                'monthly_due' => $record['monthly_due'] ?? 500,
                'password' => null,
            ];

            if ($member) {
                $member->update($memberData);
            } else {
                Member::create(array_merge(['id' => $memberId], $memberData));
            }

            $count++;
        }

        return $count;
    }

    private function importTransactions(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $memberId = $this->resolveMemberId($record);
            $sourceId = (string) ($record['id'] ?? Str::random(20));

            Transaction::updateOrCreate([
                'source_id' => $sourceId,
            ], [
                'member_id' => $memberId ?? $this->generateFallbackMember($record),
                'member_name' => $record['member'] ?? null,
                'member_email' => $record['memberEmail'] ?? null,
                'member_uid' => $record['memberUID'] ?? null,
                'type' => $record['type'] ?? 'deposit',
                'amount' => $record['amount'] ?? 0,
                'note' => $record['note'] ?? null,
                'date' => $this->parseDate($record['date'] ?? null) ?? now()->toDateString(),
                'created_by' => $memberId,
                'paymentForYear' => $record['paymentForYear'] ?? null,
                'paymentForMonth' => $record['paymentForMonth'] ?? null,
                'source_payload' => $record,
            ]);

            $count++;
        }

        return $count;
    }

    private function importProjects(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $sourceId = (string) ($record['id'] ?? Str::random(20));
            $project = Project::updateOrCreate([
                'source_id' => $sourceId,
            ], [
                'name' => $record['name'] ?? 'Untitled project',
                'description' => $record['notes'] ?? $record['description'] ?? null,
                'type' => $record['sector'] ?? $record['type'] ?? null,
                'status' => $record['status'] ?? 'active',
                'capital' => $record['capitalDeployed'] ?? $record['amount'] ?? $record['capital'] ?? 0,
                'returned' => $record['actualReturn'] ?? $record['returned'] ?? 0,
                'expected' => $record['expectedReturn'] ?? $record['expected'] ?? 0,
                'team' => $this->resolveTeamLabel($record['teamMembers'] ?? null),
                'started_at' => $this->parseDate($record['date'] ?? $record['started_at'] ?? null),
                'capitalSource' => $record['capitalSource'] ?? null,
                'projectManagerId' => $record['projectManagerId'] ?? null,
                'projectManagerName' => $record['projectManagerName'] ?? null,
                'teamEntries' => $record['teamEntries'] ?? null,
                'teamMembers' => $record['teamMembers'] ?? null,
                'collections' => $record['collections'] ?? null,
                'sales' => $record['sales'] ?? null,
                'buyers' => $record['buyers'] ?? null,
                'projectExpenses' => $record['projectExpenses'] ?? null,
                'capitalEntries' => $record['capitalEntries'] ?? null,
                'phases' => $record['milestones'] ?? $record['phases'] ?? null,
                'partner' => $record['partner'] ?? null,
                'amount' => $record['amount'] ?? $record['capitalDeployed'] ?? $record['capital'] ?? 0,
                'capitalDeployed' => $record['capitalDeployed'] ?? $record['amount'] ?? $record['capital'] ?? 0,
                'expectedReturn' => $record['expectedReturn'] ?? $record['expected'] ?? 0,
                'actualReturn' => $record['actualReturn'] ?? $record['returned'] ?? 0,
                'sector' => $record['sector'] ?? $record['type'] ?? null,
                'date' => $this->parseDate($record['date'] ?? $record['started_at'] ?? null),
                'notes' => $record['notes'] ?? $record['description'] ?? null,
                'source_payload' => $record,
            ]);

            ProjectCollection::where('project_id', $project->id)->delete();
            ProjectMilestone::where('project_id', $project->id)->delete();

            $this->importProjectCollections($project, $record['collections'] ?? []);
            $this->importProjectMilestones($project, $record['milestones'] ?? $record['phases'] ?? []);

            $count++;
        }

        return $count;
    }

    private function importProjectCollections(Project $project, array $collections): void
    {
        foreach ($collections as $collection) {
            if (! is_array($collection)) {
                continue;
            }

            $project->collections()->create([
                'collected_kg' => $collection['kg'] ?? $collection['collected_kg'] ?? 0,
                'sold_kg' => $collection['soldKg'] ?? $collection['sold_kg'] ?? 0,
                'revenue' => $collection['revenue'] ?? 0,
                'note' => $collection['note'] ?? null,
                'plastic_type' => $collection['plasticType'] ?? null,
                'price_per_kg' => $collection['pricePerKg'] ?? null,
                'sale_note' => $collection['saleNote'] ?? null,
                'source' => $collection['source'] ?? null,
                'unit' => $collection['unit'] ?? null,
                'recorded_by_name' => $collection['addedBy'] ?? null,
                'collected_at' => $this->parseDate($collection['date'] ?? $collection['collectedAt'] ?? null),
                'added_at' => $this->parseDateTime($collection['addedAt'] ?? null),
                'recorded_by' => $this->resolveMemberId($collection, ['addedBy', 'recordedBy', 'memberId']),
                'source_payload' => $collection,
            ]);
        }
    }

    private function importProjectMilestones(Project $project, array $milestones): void
    {
        $sortOrder = 0;

        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }

            $project->milestones()->create([
                'title' => $milestone['title'] ?? 'Milestone',
                'note' => $milestone['note'] ?? null,
                'achieved' => (bool) ($milestone['done'] ?? $milestone['achieved'] ?? false),
                'achieved_at' => $this->parseDate($milestone['date'] ?? $milestone['achieved_at'] ?? null),
                'sort_order' => $sortOrder++,
                'source_payload' => $milestone,
            ]);
        }
    }

    private function importProposals(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $sourceId = (string) ($record['id'] ?? Str::random(20));

            Proposal::updateOrCreate([
                'source_id' => $sourceId,
            ], [
                'title' => $record['title'] ?? 'Untitled proposal',
                'description' => $record['description'] ?? '',
                'amount' => $record['amount'] ?? null,
                'date' => $this->parseDate($record['date'] ?? null),
                'proposed_by' => $record['proposedBy'] ?? $record['proposed_by'] ?? null,
                'status' => $record['status'] ?? 'active',
                'votes_yes' => $record['votesYes'] ?? [],
                'votes_no' => $record['votesNo'] ?? [],
                'comments' => $record['comments'] ?? [],
                'source_payload' => $record,
                'created_at' => $this->parseDateTime($record['createdAt'] ?? null),
            ]);

            $count++;
        }

        return $count;
    }

    private function importNotices(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $sourceId = (string) ($record['id'] ?? Str::random(20));

            Notice::updateOrCreate([
                'source_id' => $sourceId,
            ], [
                'type' => $record['type'] ?? 'announcement',
                'title' => $record['title'] ?? null,
                'body' => $record['message'] ?? $record['body'] ?? null,
                'pinned' => (bool) ($record['pinned'] ?? false),
                'posted_by' => $record['author'] ?? $record['posted_by'] ?? null,
                'source_payload' => $record,
                'created_at' => $this->parseDateTime($record['createdAt'] ?? null),
            ]);

            $count++;
        }

        return $count;
    }

    private function importActivity(array $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $sourceId = (string) ($record['id'] ?? Str::random(20));

            ActivityLog::updateOrCreate([
                'source_id' => $sourceId,
            ], [
                'action' => $record['action'] ?? 'activity',
                'description' => $record['detail'] ?? null,
                'performed_by' => $record['by'] ?? null,
                'performed_by_name' => $record['by'] ?? null,
                'performed_by_email' => $record['byEmail'] ?? null,
                'performed_by_role' => $record['role'] ?? null,
                'iso' => $this->parseDateTime($record['iso'] ?? null),
                'ts' => $this->normalizeTimestamp($record['ts'] ?? null),
                'source_payload' => $record,
                'created_at' => $this->parseDateTime($record['iso'] ?? null),
            ]);

            $count++;
        }

        return $count;
    }

    private function resolveMemberId(array $record, array $nameKeys = ['memberUID', 'memberUid', 'member_id', 'memberId', 'addedBy', 'recordedBy']): ?string
    {
        $identifiers = [];

        foreach ($nameKeys as $key) {
            if (! empty($record[$key])) {
                $identifiers[] = (string) $record[$key];
            }
        }

        if (! empty($record['memberEmail'])) {
            $identifiers[] = (string) $record['memberEmail'];
        }

        if (! empty($record['member'])) {
            $identifiers[] = (string) $record['member'];
        }

        foreach (array_unique($identifiers) as $identifier) {
            $member = Member::query()
                ->where('id', $identifier)
                ->orWhere('google_uid', $identifier)
                ->orWhere('email', $identifier)
                ->orWhere('google_email', $identifier)
                ->first();

            if ($member) {
                return $member->id;
            }
        }

        return null;
    }

    private function generateFallbackMember(array $record): string
    {
        $email = $record['memberEmail'] ?? null;
        $name = $record['member'] ?? 'Imported member';

        $member = Member::create([
            'id' => $this->generateMemberId(),
            'name' => $name,
            'email' => $email ?? Str::lower(Str::random(12)).'@sipr.local',
            'phone' => null,
            'title' => 'Member',
            'role' => 'member',
            'locked' => false,
            'status' => 'active',
            'monthly_due' => 500,
            'password' => null,
        ]);

        return $member->id;
    }

    private function generateMemberId(): string
    {
        return Str::upper(Str::random(20));
    }

    private function findImportedMember(array $record, string $memberId): ?Member
    {
        $email = $record['email'] ?? null;

        if ($email) {
            $member = Member::where('email', $email)->first();

            if ($member) {
                return $member;
            }
        }

        $googleEmail = $record['googleEmail'] ?? null;

        if ($googleEmail) {
            $member = Member::where('google_email', $googleEmail)->first();

            if ($member) {
                return $member;
            }
        }

        $googleUid = $record['googleUid'] ?? null;

        if ($googleUid) {
            $member = Member::where('google_uid', $googleUid)->first();

            if ($member) {
                return $member;
            }
        }

        return Member::where('id', $memberId)->first();
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTeamLabel(mixed $value): string
    {
        if (is_array($value) && $value !== []) {
            $first = $value[0];

            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return 'all';
    }

    private function normalizeTimestamp(mixed $value): ?int
    {
        if (is_array($value)) {
            if (isset($value['_seconds'])) {
                return (int) $value['_seconds'];
            }

            if (isset($value['seconds'])) {
                return (int) $value['seconds'];
            }

            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
