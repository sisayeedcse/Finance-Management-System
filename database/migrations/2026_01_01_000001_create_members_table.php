<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('title', 100)->nullable();
            $table->enum('role', ['admin', 'finance', 'secretary', 'member'])->default('member');
            $table->tinyInteger('locked')->default(0);
            $table->enum('status', ['active', 'pending', 'rejected'])->default('active');
            $table->string('google_uid', 200)->nullable();
            $table->string('google_email', 150)->nullable();
            $table->decimal('monthly_due', 10, 2)->default(500.00);
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
