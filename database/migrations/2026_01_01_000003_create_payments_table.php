<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 20);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 10, 2)->default(500.00);
            $table->date('paid_at')->nullable();
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->string('recorded_by', 20)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'month', 'year']);
            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
