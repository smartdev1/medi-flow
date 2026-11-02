<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("surgery_checklists", function (Blueprint $table) {
            $table->id();
            $table->foreignId("surgery_id")->constrained("surgeries")->cascadeOnDelete();
            $table->string("phase");
            $table->foreignId("checked_by")->constrained("users");
            $table->dateTime("checked_at");
            $table->json("items");
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->unique(["surgery_id", "phase"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("surgery_checklists");
    }
};
