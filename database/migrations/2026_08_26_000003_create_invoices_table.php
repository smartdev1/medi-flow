<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->integer('price')->default(0)->after('name');
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->integer('rate')->default(0)->after('name');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->integer('subtotal')->default(0);
            $table->integer('insurance_amount')->default(0);
            $table->integer('patient_amount')->default(0);
            $table->integer('paid_amount')->default(0);
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
