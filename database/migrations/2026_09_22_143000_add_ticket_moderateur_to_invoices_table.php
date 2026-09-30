<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('ticket_moderateur', 5, 2)->default(20)->after('patient_amount');
            $table->integer('base_amount')->default(0)->after('ticket_moderateur');
            $table->string('insurance_name')->nullable()->after('base_amount');
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'rate')) {
                $table->dropColumn('rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['ticket_moderateur', 'base_amount', 'insurance_name']);
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->integer('rate')->default(0)->after('name');
        });
    }
};