<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 20);
            $table->enum('type', ['deposit', 'profit', 'invest', 'expense', 'fine']);
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->date('date');
            $table->string('created_by', 20)->nullable();
            $table->timestamps();

            $table->index('member_id');
            $table->index('type');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
