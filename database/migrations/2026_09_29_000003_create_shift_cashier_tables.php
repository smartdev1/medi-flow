<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remise de fonds entre deux vacations (relevé de caisse).
        //
        // L'argent reste dans le coffre mais la responsabilité change de main :
        // le caissier entrant ouvre sa vacation avec le montant qu'il a reçu.
        // Sans cet enregistrement structuré, le fonds d'ouverture suivant
        // n'est pas réconciliable avec le comptage du caissier sortant.
        Schema::create('session_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_session_id')
                ->constrained('cashier_sessions')
                ->cascadeOnDelete();
            $table->foreignId('to_session_id')
                ->nullable()
                ->constrained('cashier_sessions')
                ->nullOnDelete();
            $table->foreignId('from_cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('amount');
            $table->timestamp('transferred_at');
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Un caissier ne reçoit qu'une seule remise par session cible.
            $table->unique(['to_session_id'], 'session_transfers_unique_target');
        });

        // Versement journalier.
        //
        // Rattaché aux vacations et non à une date civile : une vacation de
        // 22h à 6h est versée le lendemain mais couvre deux dates.
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->integer('amount');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->timestamp('deposited_at');
            $table->foreignId('deposited_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'cashier_id']);
        });

        Schema::create('versement_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('versement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_session_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versement_session');
        Schema::dropIfExists('versements');
        Schema::dropIfExists('session_transfers');
    }
};
