<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->decimal('collected_kg', 8, 3)->default(0.000);
            $table->decimal('sold_kg', 8, 3)->default(0.000);
            $table->decimal('revenue', 12, 2)->default(0.00);
            $table->text('note')->nullable();
            $table->date('collected_at');
            $table->string('recorded_by', 20)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_collections');
    }
};
