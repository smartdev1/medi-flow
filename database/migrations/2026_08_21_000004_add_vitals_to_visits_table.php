<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('vitals_taken_by')->nullable()->after('urgent')->constrained('users')->nullOnDelete();
            $table->timestamp('vitals_taken_at')->nullable()->after('vitals_taken_by');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vitals_taken_by');
            $table->dropColumn('vitals_taken_at');
        });
    }
};
