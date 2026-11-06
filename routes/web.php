<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\ActController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AttenteController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BlocController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\DocteurGeneralController;
use App\Http\Controllers\EtatQuotidienController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HospitalisationController;
use App\Http\Controllers\InvoiceController;
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
use App\Http\Controllers\SurgeryConsentController;
use App\Http\Controllers\SurgeryController;
use App\Http\Controllers\SurgeryOperativeReportController;
use App\Http\Controllers\SurgeryRecoveryController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return redirect()->route(auth()->check() ? auth()->user()->role->homeRouteName() : "login");
});

Route::middleware(["auth", "verified"])->group(function () {
    Route::middleware("role:accueil,docteur_general,aide_soignante")->group(function () {
        Route::get("/accueil", [AccueilController::class, "index"])->name("accueil.index");
        Route::get("/patients", [PatientController::class, "index"])->name("patients.index");
        Route::post("/patients", [PatientController::class, "store"])->name("patients.store");
        Route::get("/patients/{patient}", [PatientController::class, "show"])->name("patients.show");
        Route::get("/salle-attente", [AttenteController::class, "index"])->name("attente.index");
        Route::get("/salle-attente/refresh", [AttenteController::class, "refresh"])->name("attente.refresh");
    });

    Route::middleware("role:accueil")->group(function () {
        Route::post("/accueil/visites/{visit}/montant-sejour", [AccueilController::class, "updateStayAmount"])->name("accueil.visits.stay-amount");
    });

    Route::middleware("role:aide_soignante,medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,docteur_general")->group(function () {
        Route::post("/visites/{visit}/constantes", [AttenteController::class, "markVitals"])->name("visits.vitals");
        Route::get("/admission-requests", [HospitalisationController::class, "pendingRequests"])->name("admission-requests.index");
        Route::post("/admission-requests/{admissionRequest}/approve", [HospitalisationController::class, "approveRequest"])->name("admission-requests.approve");
        Route::post("/admission-requests/{admissionRequest}/reject", [HospitalisationController::class, "rejectRequest"])->name("admission-requests.reject");
    });

    Route::middleware("role:accueil,docteur_general")->group(function () {
        Route::get("/sortie", [SortieController::class, "index"])->name("sortie.index");
        Route::post("/sortie/{visit}/cloturer", [SortieController::class, "close"])->name("sortie.close");
    });

    Route::middleware("role:caissier,docteur_general,raf")->group(function () {
        Route::patch("/surgeries/{surgery}/cost", [SurgeryController::class, "updateCost"])->name("surgery.update-cost");
        Route::get("/caisse", [CaisseController::class, "index"])->name("caisse.index");
        Route::get("/caisse/refresh", [CaisseController::class, "refresh"])->name("caisse.refresh");
        Route::get("/caisse/historique", [CaisseController::class, "history"])->name("caisse.historique");
        Route::get("/caisse/jour/{date}", [CaisseController::class, "showDay"])->name("caisse.jour");
        Route::post("/paiements", [PaymentController::class, "store"])->name("payments.store");
        Route::post("/paiements/{payment}/annuler", [PaymentController::class, "cancel"])->name("payments.cancel");
        Route::post("/caisse/versement", [PaymentController::class, "deposit"])->name("caisse.versement");
        Route::post("/caisse/sessions/{session}/releve", [PaymentController::class, "handOver"])->name("caisse.sessions.handover");
        Route::get("/paiements/{payment}/recu", [PaymentController::class, "receipt"])->name("payments.receipt")->where("payment", ".*");
        Route::get("/paiements/{payment}/recu/pdf", [PaymentController::class, "downloadReceipt"])->name("payments.receipt.download")->where("payment", ".*");
        Route::get("/caisse/paniers/{invoice}", [InvoiceController::class, "show"])->name("caisse.paniers.show");
        Route::post("/caisse/paniers/{invoice}/payer", [InvoiceController::class, "pay"])->name("caisse.paniers.pay");

        Route::get("/caisse/sessions", [App\Http\Controllers\CashierSessionController::class, "index"])->name("caisse.sessions.index");
        Route::get("/caisse/sessions/{cashierSession}", [App\Http\Controllers\CashierSessionController::class, "show"])->name("caisse.sessions.show");
        Route::get("/caisse/session/open", [App\Http\Controllers\CashierSessionController::class, "openForm"])->name("caisse.sessions.open");
        Route::post("/caisse/session/open", [App\Http\Controllers\CashierSessionController::class, "open"])->name("caisse.sessions.open.store");
        Route::get("/caisse/session/close", [App\Http\Controllers\CashierSessionController::class, "closeForm"])->name("caisse.sessions.close");
        Route::post("/caisse/session/close", [App\Http\Controllers\CashierSessionController::class, "close"])->name("caisse.sessions.close.store");
        Route::get("/caisse/sessions/{cashierSession}/fermeture-urgence", [App\Http\Controllers\CashierSessionController::class, "emergencyCloseForm"])->name("caisse.sessions.emergency-close-form");
        Route::post("/caisse/sessions/{cashierSession}/fermeture-urgence", [App\Http\Controllers\CashierSessionController::class, "emergencyClose"])->name("caisse.sessions.emergency-close");
    });

    Route::middleware("role:raf,admin,docteur_general")->group(function () {
        Route::get("/caisse/raf", [App\Http\Controllers\CashierSessionController::class, "rafDashboard"])->name("caisse.raf.dashboard");
    });

    Route::middleware("role:medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,docteur_general")->group(function () {
        Route::get("/medecin", [MedecinController::class, "index"])->name("medecin.index");
        Route::get("/medecin/refresh", [MedecinController::class, "refresh"])->name("medecin.refresh");
        Route::post("/medecin/visites/{visit}/take", [MedecinController::class, "takeVisit"])->name("medecin.visits.take");
        Route::post("/medecin/visites/{visit}/hospitalize", [MedecinController::class, "requestHospitalization"])->name("medecin.visits.hospitalize");
        Route::post("/medecin/visites/{visit}/observe", [MedecinController::class, "requestObservation"])->name("medecin.visits.observe");
        Route::post("/consultations/{consultation}", [ConsultationController::class, "update"])->name("consultations.update");
        Route::post("/consultations/{consultation}/cloturer", [ConsultationController::class, "close"])->name("consultations.close");
        Route::post("/admissions", [AdmissionController::class, "store"])->name("admissions.store");
        Route::post("/admissions/{admission}/sortie-prevue", [AdmissionController::class, "planDischarge"])->name("admissions.plan-discharge");
        Route::post("/admissions/{admission}/sortie-annulee", [AdmissionController::class, "cancelPlannedDischarge"])->name("admissions.cancel-discharge");
        Route::post("/admissions/{admission}/sortie", [AdmissionController::class, "discharge"])->name("admissions.discharge");
        Route::post("/chambres", [ChambreController::class, "store"])->name("chambres.store");
        Route::patch("/chambres/{room}/maintenance", [ChambreController::class, "toggleMaintenance"])->name("chambres.toggle-maintenance");
        Route::delete("/chambres/{room}", [ChambreController::class, "destroy"])->name("chambres.destroy");
        Route::post("/orientations", [ReferralController::class, "store"])->name("referrals.store");
        Route::post("/medecin/visites/{visit}/surgery", [SurgeryController::class, "store"])->name("surgery.store");
        Route::patch("/surgeries/{surgery}", [SurgeryController::class, "update"])->name("surgery.update");
        Route::delete("/surgeries/{surgery}", [SurgeryController::class, "destroy"])->name("surgery.destroy");
        Route::patch("/surgeries/{surgery}/pre-anesthetic", [SurgeryConsentController::class, "preAnesthetic"])->name("surgery.pre-anesthetic");
        Route::post("/surgeries/{surgery}/start", [SurgeryController::class, "start"])->name("surgery.start");
        Route::post("/surgeries/{surgery}/complete", [SurgeryController::class, "complete"])->name("surgery.complete");
        Route::post("/surgeries/{surgery}/operative-report", [SurgeryOperativeReportController::class, "store"])->name("surgery.operative-report");
    });

    Route::middleware("role:medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,aide_soignante,docteur_general")->group(function () {
        Route::post("/surgeries/{surgery}/recovery/start", [SurgeryRecoveryController::class, "start"])->name("surgery.recovery.start");
        Route::post("/surgeries/{surgery}/recovery/end", [SurgeryRecoveryController::class, "end"])->name("surgery.recovery.end");
    });

    Route::middleware("role:medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,aide_soignante")->group(function () {
        Route::get("/bloc", [BlocController::class, "index"])->name("bloc.index");
        Route::get("/bloc/refresh", [BlocController::class, "refresh"])->name("bloc.refresh");
    });

    Route::middleware("role:medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,aide_soignante,admin,docteur_general")->group(function () {
        Route::get("/hospitalisation", [HospitalisationController::class, "index"])->name("hospitalisation.index");
        Route::get("/hospitalisation/refresh", [HospitalisationController::class, "refresh"])->name("hospitalisation.refresh");
        Route::get("/observation", [HospitalisationController::class, "observation"])->name("observation.index");
        Route::get("/observation/refresh", [HospitalisationController::class, "observationRefresh"])->name("observation.refresh");
        Route::get("/chambres", [ChambreController::class, "index"])->name("chambres.index");
    });

    Route::middleware("role:aide_soignante,admin,docteur_general")->group(function () {
        Route::post("/admissions/{admission}/valider-depiquage", [AdmissionController::class, "validateDepiquage"])->name("admissions.valider-depiquage");
        Route::post("/admissions/{admission}/liberer-chambre", [AdmissionController::class, "libererChambre"])->name("admissions.liberer-chambre");
    });

    Route::middleware("role:accueil,docteur_general,admin,aide_soignante")->group(function () {
        Route::get("/etat-quotidien", [EtatQuotidienController::class, "index"])->name("etat-quotidien.index");
        Route::get("/etat-quotidien/export-csv", [EtatQuotidienController::class, "exportCsv"])->name("etat-quotidien.export-csv");
    });

    Route::middleware("role:admin,docteur_general")->group(function () {
        Route::get("/users", [UserController::class, "index"])->name("users.index");
        Route::get("/insurance", [InsuranceController::class, "index"])->name("insurance.index");
        Route::get("/personnel", [PersonnelController::class, "index"])->name("personnel.index");
    });

    Route::middleware("role:admin")->group(function () {
        Route::post("/users", [UserController::class, "store"])->name("users.store");
        Route::put("/users/{user}", [UserController::class, "update"])->name("users.update");
        Route::post("/users/{user}/toggle", [UserController::class, "toggle"])->name("users.toggle");

        Route::post("/insurance", [InsuranceController::class, "store"])->name("insurance.store");
        Route::put("/insurance/{insuranceCompany}", [InsuranceController::class, "update"])->name("insurance.update");
        Route::delete("/insurance/{insuranceCompany}", [InsuranceController::class, "destroy"])->name("insurance.destroy");

        Route::post("/personnel", [PersonnelController::class, "store"])->name("personnel.store");
        Route::patch("/personnel/{doctor}", [PersonnelController::class, "update"])->name("personnel.update");
        Route::post("/personnel/{doctor}/statut", [PersonnelController::class, "toggleActive"])->name("personnel.toggleActive");
        Route::post("/gardes", [OnCallShiftController::class, "store"])->name("on-call.store");
        Route::delete("/gardes/{onCallShift}", [OnCallShiftController::class, "destroy"])->name("on-call.destroy");
    });

    Route::middleware("role:admin,docteur_general")->group(function () {
        Route::get("/acts", [ActController::class, "index"])->name("acts.index");
    });

    Route::middleware("role:admin")->group(function () {
        Route::post("/acts", [ActController::class, "store"])->name("acts.store");
        Route::put("/acts/{act}", [ActController::class, "update"])->name("acts.update");
        Route::delete("/acts/{act}", [ActController::class, "destroy"])->name("acts.destroy");
    });

    Route::middleware("role:laborantin,medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,docteur_general")->group(function () {
        Route::post("/examens", [ExamController::class, "store"])->name("exams.store");
        Route::get("/examens/recherche", [ExamController::class, "search"])->name("exams.search");
    });

    Route::middleware("role:laborantin,docteur_general")->group(function () {
        Route::get("/laboratoire", [LaboratoireController::class, "index"])->name("laboratoire.index");
        Route::get("/laboratoire/refresh", [LaboratoireController::class, "refresh"])->name("laboratoire.refresh");
        Route::post("/examens/{exam}/avancer", [ExamController::class, "advance"])->name("exams.advance");
    });

    Route::middleware("role:medecin,pediatre,chirurgien,anesthesiste,cardiologue,dermatologue,orl,ophtalmo,dentiste,docteur_general")->group(function () {
        Route::post("/ordonnances", [PrescriptionController::class, "store"])->name("prescriptions.store");
    });

    Route::middleware("role:pharmacien,docteur_general")->group(function () {
        Route::get("/pharmacie", [PharmacieController::class, "index"])->name("pharmacie.index");
        Route::get("/pharmacie/patients/recherche", [PharmacieController::class, "searchPatients"])->name("pharmacie.patients.search");
        Route::get("/pharmacie/medicaments/recherche", [PharmacieController::class, "searchMedications"])->name("pharmacie.medications.search");
        Route::post("/ordonnances/{prescription}/delivrer", [PrescriptionController::class, "dispense"])->name("prescriptions.dispense");
        Route::post("/ventes", [PharmacieController::class, "sell"])->name("pharmacie.sell");
    });

    Route::middleware("role:logistique,docteur_general")->group(function () {
        Route::get("/logistique", [LogistiqueController::class, "index"])->name("logistique.index");
        Route::post("/medicaments", [MedicationController::class, "store"])->name("medications.store");
        Route::post("/medicaments/{medication}/stock", [MedicationController::class, "adjustStock"])->name("medications.adjust-stock");
        Route::post("/commandes", [MedicationOrderController::class, "store"])->name("medication-orders.store");
        Route::post("/commandes/{medicationOrder}/reception", [MedicationOrderController::class, "receive"])->name("medication-orders.receive");
        Route::get("/commandes/{medicationOrder}/imprimer", [MedicationOrderController::class, "print"])->name("medication-orders.print");
    });

    Route::middleware("role:docteur_general")->group(function () {
        Route::get("/docteur-general", [DocteurGeneralController::class, "index"])->name("docteurgeneral.index");
    });
});

Route::middleware("auth")->group(function () {
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::get("/api/badges", [BadgeController::class, "counts"])->name("api.badges");
});

require __DIR__."/auth.php";
