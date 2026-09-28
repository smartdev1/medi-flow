<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Patient::class => \App\Policies\PatientPolicy::class,
        Payment::class => \App\Policies\PaymentPolicy::class,
        Exam::class => \App\Policies\ExamPolicy::class,
        Invoice::class => \App\Policies\InvoicePolicy::class,
        CashierSession::class => \App\Policies\CashierSessionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Admin bypass for all gates
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });
    }
}
