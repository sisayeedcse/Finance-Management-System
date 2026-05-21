<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_registrations', 'invite_code')) {
                $table->string('invite_code', 50)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('pending_registrations', 'password')) {
                $table->string('password')->nullable()->after('invite_code');
            }

            if (!Schema::hasColumn('pending_registrations', 'approved_role')) {
                $table->string('approved_role', 20)->nullable()->after('status');
            }

            if (!Schema::hasColumn('pending_registrations', 'approved_by')) {
                $table->string('approved_by', 20)->nullable()->after('approved_role');
            }

            if (!Schema::hasColumn('pending_registrations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pending_registrations', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('pending_registrations', 'approved_by')) {
                $table->dropColumn('approved_by');
            }

            if (Schema::hasColumn('pending_registrations', 'approved_role')) {
                $table->dropColumn('approved_role');
            }

            if (Schema::hasColumn('pending_registrations', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('pending_registrations', 'invite_code')) {
                $table->dropColumn('invite_code');
            }
        });
    }
};