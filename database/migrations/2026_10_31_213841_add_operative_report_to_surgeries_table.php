<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("surgeries", function (Blueprint $table) {
            $table->text("operative_report")->nullable();
            $table->foreignId("operative_report_by")->nullable()->constrained("users")->nullOnDelete();
            $table->dateTime("operative_report_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::table("surgeries", function (Blueprint $table) {
            $table->dropConstrainedForeignId("operative_report_by");
            $table->dropColumn(["operative_report", "operative_report_by", "operative_report_at"]);
        });
    }
};
