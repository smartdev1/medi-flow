<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AttenteController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HospitalisationController;
use App\Http\Controllers\LaboratoireController;
use App\Http\Controllers\LogistiqueController;
use App\Http\Controllers\MedecinController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationOrderController;
use App\Http\Controllers\OnCallShiftController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PharmacieController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SortieController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route(auth()->check() ? auth()->user()->role->homeRouteName() : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:accueil')->group(function () {
        Route::get('/accueil', [AccueilController::class, 'index'])->name('accueil.index');
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::get('/salle-attente', [AttenteController::class, 'index'])->name('attente.index');
    });

    Route::middleware('role:accueil,caissier')->group(function () {
        Route::get('/sortie', [SortieController::class, 'index'])->name('sortie.index');
        Route::post('/sortie/{visit}/cloturer', [SortieController::class, 'close'])->name('sortie.close');
    });

    Route::middleware('role:caissier')->group(function () {
        Route::get('/caisse', [CaisseController::class, 'index'])->name('caisse.index');
        Route::get('/caisse/historique', [CaisseController::class, 'history'])->name('caisse.historique');
        Route::get('/caisse/jour/{date}', [CaisseController::class, 'showDay'])->name('caisse.jour');
        Route::post('/paiements', [PaymentController::class, 'store'])->name('payments.store');
        Route::post('/caisse/cloturer', [PaymentController::class, 'closeDay'])->name('caisse.cloturer');
        Route::get('/paiements/{payment}/recu', [PaymentController::class, 'receipt'])->name('payments.receipt')->where('payment', '.*');
        Route::get('/paiements/{payment}/recu/pdf', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt.download')->where('payment', '.*');
    });

    // Route pour encaisser un paiement - accessible aux caissiers
    Route::post('/paiements/encaisser', [PaymentController::class, 'markAsPaid'])->name('payments.encaisser')->middleware('role:caissier');

    Route::middleware('role:medecin,pediatre')->group(function () {
        Route::get('/medecin', [MedecinController::class, 'index'])->name('medecin.index');
        Route::post('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
        Route::post('/consultations/{consultation}/cloturer', [ConsultationController::class, 'close'])->name('consultations.close');
        Route::get('/hospitalisation', [HospitalisationController::class, 'index'])->name('hospitalisation.index');
        Route::post('/admissions', [AdmissionController::class, 'store'])->name('admissions.store');
        Route::post('/admissions/{admission}/sortie', [AdmissionController::class, 'discharge'])->name('admissions.discharge');
        Route::get('/chambres', [ChambreController::class, 'index'])->name('chambres.index');
        Route::post('/chambres', [ChambreController::class, 'store'])->name('chambres.store');
        Route::patch('/chambres/{room}/maintenance', [ChambreController::class, 'toggleMaintenance'])->name('chambres.toggle-maintenance');
        Route::delete('/chambres/{room}', [ChambreController::class, 'destroy'])->name('chambres.destroy');
        Route::post('/orientations', [ReferralController::class, 'store'])->name('referrals.store');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

        Route::get('/insurance', [InsuranceController::class, 'index'])->name('insurance.index');
        Route::post('/insurance', [InsuranceController::class, 'store'])->name('insurance.store');
        Route::put('/insurance/{insuranceCompany}', [InsuranceController::class, 'update'])->name('insurance.update');
        Route::delete('/insurance/{insuranceCompany}', [InsuranceController::class, 'destroy'])->name('insurance.destroy');

        Route::get('/personnel', [PersonnelController::class, 'index'])->name('personnel.index');
        Route::post('/personnel', [PersonnelController::class, 'store'])->name('personnel.store');
        Route::patch('/personnel/{doctor}', [PersonnelController::class, 'update'])->name('personnel.update');
        Route::post('/personnel/{doctor}/statut', [PersonnelController::class, 'toggleActive'])->name('personnel.toggle');
        Route::post('/assurances', [PersonnelController::class, 'storeInsuranceCompany'])->name('insurance-companies.store');
        Route::post('/gardes', [OnCallShiftController::class, 'store'])->name('on-call.store');
        Route::delete('/gardes/{onCallShift}', [OnCallShiftController::class, 'destroy'])->name('on-call.destroy');
    });

    Route::middleware('role:laborantin,medecin,pediatre')->group(function () {
        Route::post('/examens', [ExamController::class, 'store'])->name('exams.store');
    });

    Route::middleware('role:laborantin')->group(function () {
        Route::get('/laboratoire', [LaboratoireController::class, 'index'])->name('laboratoire.index');
        Route::post('/examens/{exam}/avancer', [ExamController::class, 'advance'])->name('exams.advance');
    });

    Route::middleware('role:medecin,pediatre')->group(function () {
        Route::post('/ordonnances', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    });

    Route::middleware('role:pharmacien')->group(function () {
        Route::get('/pharmacie', [PharmacieController::class, 'index'])->name('pharmacie.index');
        Route::post('/ordonnances/{prescription}/delivrer', [PrescriptionController::class, 'dispense'])->name('prescriptions.dispense');
    });

    Route::middleware('role:logistique')->group(function () {
        Route::get('/logistique', [LogistiqueController::class, 'index'])->name('logistique.index');
        Route::post('/medicaments', [MedicationController::class, 'store'])->name('medications.store');
        Route::post('/medicaments/{medication}/stock', [MedicationController::class, 'adjustStock'])->name('medications.adjust-stock');
        Route::post('/commandes', [MedicationOrderController::class, 'store'])->name('medication-orders.store');
        Route::post('/commandes/{medicationOrder}/reception', [MedicationOrderController::class, 'receive'])->name('medication-orders.receive');
        Route::get('/commandes/{medicationOrder}/imprimer', [MedicationOrderController::class, 'print'])->name('medication-orders.print');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
