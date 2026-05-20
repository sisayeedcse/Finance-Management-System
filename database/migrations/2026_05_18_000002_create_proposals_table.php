<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->text('description');
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('date')->nullable();
            $table->string('proposed_by', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->json('votes_yes')->nullable();
            $table->json('votes_no')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
