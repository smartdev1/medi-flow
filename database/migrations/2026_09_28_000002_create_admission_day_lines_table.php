<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_day_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->date('day_date');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique(['admission_id', 'day_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_day_lines');
    }
};
