<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->foreignId('emergency_closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('closing_balance');

            $table->timestamp('emergency_closed_at')
                ->nullable()
                ->after('emergency_closed_by');

            $table->string('emergency_closure_reason', 1000)
                ->nullable()
                ->after('emergency_closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->dropForeign(['emergency_closed_by']);
            $table->dropColumn([
                'emergency_closed_by',
                'emergency_closed_at',
                'emergency_closure_reason',
            ]);
        });
    }
};
