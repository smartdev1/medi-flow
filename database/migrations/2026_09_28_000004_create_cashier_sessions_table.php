<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedInteger('opening_balance');
            $table->unsignedInteger('closing_balance')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('cashier_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cashier_session_id')->nullable()->constrained('cashier_sessions')->nullOnDelete()->after('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cashier_session_id']);
            $table->dropColumn('cashier_session_id');
        });

        Schema::dropIfExists('cashier_sessions');
    }
};
