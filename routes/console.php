<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\FirestoreBackupImportService;

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
