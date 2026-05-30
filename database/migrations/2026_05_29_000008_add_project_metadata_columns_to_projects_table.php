<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('capitalSource', 150)->nullable()->after('started_at');
            $table->string('projectManagerId', 20)->nullable()->after('capitalSource');
            $table->string('projectManagerName', 150)->nullable()->after('projectManagerId');
            $table->json('teamEntries')->nullable()->after('projectManagerName');
            $table->json('collections')->nullable()->after('teamEntries');
            $table->json('sales')->nullable()->after('collections');
            $table->json('projectExpenses')->nullable()->after('sales');
            $table->json('capitalEntries')->nullable()->after('projectExpenses');
            $table->json('phases')->nullable()->after('capitalEntries');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'capitalSource',
                'projectManagerId',
                'projectManagerName',
                'teamEntries',
                'collections',
                'sales',
                'projectExpenses',
                'capitalEntries',
                'phases',
            ]);
        });
    }
};