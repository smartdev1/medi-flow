<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreignId('medication_id')->nullable()->after('prescription_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1)->after('name');
            $table->unsignedInteger('unit_price')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medication_id');
            $table->dropColumn(['quantity', 'unit_price']);
        });
    }
};
