<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('prescribed_by')->nullable()->after('patient_id')->constrained('users')->nullOnDelete();
            $table->foreignId('dispensed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('dispensed_at')->nullable()->after('dispensed_by');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prescribed_by');
            $table->dropConstrainedForeignId('dispensed_by');
            $table->dropColumn('dispensed_at');
        });
    }
};
