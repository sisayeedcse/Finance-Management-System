<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('google_email');
            $table->string('gmail', 150)->nullable()->after('photo');
            $table->string('wa_link', 255)->nullable()->after('gmail');
            $table->string('address', 255)->nullable()->after('wa_link');
            $table->string('emoji', 50)->nullable()->after('address');
            $table->json('permissions')->nullable()->after('emoji');
            $table->timestamp('registered_at')->nullable()->after('permissions');
            $table->timestamp('restored_at')->nullable()->after('registered_at');
            $table->json('source_payload')->nullable()->after('restored_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('source_id', 100)->nullable()->unique()->after('id');
            $table->json('teamMembers')->nullable()->after('teamEntries');
            $table->json('buyers')->nullable()->after('teamMembers');
            $table->string('partner', 150)->nullable()->after('buyers');
            $table->json('source_payload')->nullable()->after('phases');
        });

        Schema::table('project_collections', function (Blueprint $table) {
            $table->string('plastic_type', 100)->nullable()->after('note');
            $table->decimal('price_per_kg', 12, 2)->nullable()->after('plastic_type');
            $table->text('sale_note')->nullable()->after('price_per_kg');
            $table->string('source', 100)->nullable()->after('sale_note');
            $table->string('unit', 50)->nullable()->after('source');
            $table->string('recorded_by_name', 150)->nullable()->after('unit');
            $table->timestamp('added_at')->nullable()->after('collected_at');
            $table->json('source_payload')->nullable()->after('recorded_by');
        });

        Schema::table('project_milestones', function (Blueprint $table) {
            $table->text('note')->nullable()->after('title');
            $table->json('source_payload')->nullable()->after('created_at');
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->string('source_id', 100)->nullable()->unique()->after('id');
            $table->boolean('pinned')->default(false)->after('body');
            $table->json('source_payload')->nullable()->after('posted_by');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('source_id', 100)->nullable()->unique()->after('id');
            $table->json('comments')->nullable()->after('votes_no');
            $table->json('source_payload')->nullable()->after('comments');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('source_id', 100)->nullable()->unique()->after('id');
            $table->string('performed_by_email', 150)->nullable()->after('performed_by_name');
            $table->string('performed_by_role', 50)->nullable()->after('performed_by_email');
            $table->timestamp('iso')->nullable()->after('performed_by_role');
            $table->unsignedBigInteger('ts')->nullable()->after('iso');
            $table->json('source_payload')->nullable()->after('ts');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source_id', 100)->nullable()->unique()->after('id');
            $table->string('member_name', 150)->nullable()->after('member_id');
            $table->string('member_email', 150)->nullable()->after('member_name');
            $table->string('member_uid', 150)->nullable()->after('member_email');
            $table->json('source_payload')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'member_name', 'member_email', 'member_uid', 'source_payload']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'performed_by_email', 'performed_by_role', 'iso', 'ts', 'source_payload']);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'comments', 'source_payload']);
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'pinned', 'source_payload']);
        });

        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropColumn(['note', 'source_payload']);
        });

        Schema::table('project_collections', function (Blueprint $table) {
            $table->dropColumn([
                'plastic_type',
                'price_per_kg',
                'sale_note',
                'source',
                'unit',
                'recorded_by_name',
                'added_at',
                'source_payload',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'teamMembers', 'buyers', 'partner', 'source_payload']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'photo',
                'gmail',
                'wa_link',
                'address',
                'emoji',
                'permissions',
                'registered_at',
                'restored_at',
                'source_payload',
            ]);
        });
    }
};
