<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("surgeries", function (Blueprint $table) {
            $table->dateTime("recovery_started_at")->nullable();
            $table->dateTime("recovery_ended_at")->nullable();
            $table->text("recovery_notes")->nullable();
            $table->foreignId("recovery_nurse_id")->nullable()->constrained("users")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("surgeries", function (Blueprint $table) {
            $table->dropConstrainedForeignId("recovery_nurse_id");
            $table->dropColumn(["recovery_started_at", "recovery_ended_at", "recovery_notes", "recovery_nurse_id"]);
        });
    }
};
