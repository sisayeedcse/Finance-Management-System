<?php

namespace Database\Seeders;

use App\Services\SqlFileImportService;
use Illuminate\Database\Seeder;

class FirestoreBackupSqlSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('firestore-backup.sql');

        $result = app(SqlFileImportService::class)->import($path);

        $this->command?->info(sprintf(
            'Imported firestore-backup.sql (%d statements)',
            $result['statements'],
        ));
    }
}
