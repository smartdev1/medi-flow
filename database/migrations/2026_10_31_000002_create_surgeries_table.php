<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits');
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('surgeon_id')->constrained('users');
            $table->foreignId('anesthetist_id')->nullable()->constrained('users');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->string('surgery_type');
            $table->string('procedure_name');
            $table->text('pre_anesthetic_notes')->nullable();
            $table->dateTime('consent_signed_at')->nullable();
            $table->string('consent_signed_by')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgeries');
    }
};
