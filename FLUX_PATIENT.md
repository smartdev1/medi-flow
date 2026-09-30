# Flux patient — Clinique Saint Matthieu

## Vue d'ensemble

```
Accueil → Salle d'attente → Médecin → [Hospitalisation/Observation | Laboratoire | Pharmacie] → Sortie → Caisse
```

---

## 1. Enregistrement — Accueil

**Rôle autorisé :** `accueil`, `docteur_general`
**Route :** `POST /patients`
**Contrôleur :** `PatientController::store`

### Actions
1. Création du patient (ou retour d'un patient existant)
2. Création automatique d'une **visite** (`Visit`) avec statut :
   - `En attente` (standard)
   - `Prioritaire` (si urgence)
3. Création automatique d'une **facture** (`Invoice`) au statut `En attente`
4. Redirection vers `/accueil` avec message de succès

### Règles métier
- Code patient auto-généré : `CSM-XXXX`
- Si patient existant : nouvelle visite créée, pas de doublon patient
- Lien possible vers une compagnie d'assurance (`Assuré` / `Non assuré`)

---

## 2. Salle d'attente — Prise de constantes

**Rôle autorisé :** `aide_soignante`
**Route :** `POST /visites/{visit}/constantes`
**Contrôleur :** `AttenteController::markVitals`

### Actions
1. Enregistrement des constantes vitales (tension, température, pouls, saturation)
2. Passage automatique de la visite en statut `En consultation`
3. Redirection vers `/salle-attente`

### Règles métier
- Uniquement les visites en `En attente` ou `Prioritaire`
- Après prise de constantes, le patient devient éligible pour le médecin

---

## 3. Consultation — Médecin

**Rôle autorisé :** `medecin`, `pediatre`, `docteur_general`
**Route principale :** `GET /medecin`

### 3a. Prise en charge
**Route :** `POST /medecin/visites/{visit}/take`
**Contrôleur :** `MedecinController::takeVisit`

- Changement de statut → `En consultation`
- Redirection vers `/medecin?patient=X`

### 3b. Actions pendant la consultation
- **Ordonnance** : `POST /ordonnances` → `PrescriptionController::store`
- **Examen** : `POST /examens` → `ExamController::store`
- **Demande d'hospitalisation** : `POST /medecin/visites/{visit}/hospitalize`
  - Statut → `Demande hospitalisation`
- **Demande d'observation** : `POST /medecin/visites/{visit}/observe`
  - Statut → `Demande observation`
- **Orientation** : `POST /orientations` → `ReferralController::store`
  - Statut → `Orienté`

### 3c. Clôture de consultation
**Route :** `POST /consultations/{consultation}/cloturer`
**Contrôleur :** `ConsultationController::close`

- Statut visite → `Consultation terminée`
- Facture recalculée (ajout des honoraires)
- La visite est maintenant éligible pour la sortie

---

## 4a. Hospitalisation / Observation

**Rôle autorisé pour approbation :** `aide_soignante`
**Route des demandes :** `GET /admission-requests`

### Demande
Créée par le médecin lors de la consultation. Deux types :
- `hospitalisation` → demande d'admission en chambre
- `observation` → demande de surveillance courte durée

### Approbation
**Route :** `POST /admission-requests/{request}/approve`
**Contrôleur :** `HospitalisationController::approveRequest`

1. Vérification disponibilité du lit (avec `lockForUpdate` anti-race condition)
2. Création de l'**admission** (`Admission`) avec lit assigné
3. Statut visite → `Hospitalisé` ou `Observation`
4. Facture recalculée

### Rejet
**Route :** `POST /admission-requests/{request}/reject`
**Contrôleur :** `HospitalisationController::rejectRequest`

- Statut visite → retour `En consultation`

### Sortie d'hospitalisation
**Route :** `POST /admissions/{admission}/sortie`
**Contrôleur :** `AdmissionController::discharge`

- Si observation : statut visite → `Retour observation` (retour possible en consultation)
- Si hospitalisation : la visite garde le statut `Hospitalisé`
- Sortie du lit

---

## 4b. Laboratoire

**Rôles :**
- Demande : `medecin`, `pediatre`, `docteur_general`
- Traitement : `laborantin`

### Cycle de vie d'un examen

| Étape | Statut | Action |
|-------|--------|--------|
| 1. Demande | `En attente de prélèvement` | Médecin crée l'examen |
| 2. Prélèvement | `Résultat en cours` | Laborantin avance |
| 3. Saisie résultat | `Résultat prêt` | Laborantin avance |
| 4. Envoi | `Envoyé au médecin` | Laborantin avance |

**Routes :**
- `POST /examens` → `ExamController::store`
- `POST /examens/{exam}/avancer` → `ExamController::advance`

---

## 4c. Pharmacie

**Rôle :** `pharmacien`
**Routes :**
- `POST /ordonnances/{prescription}/delivrer` → `PrescriptionController::dispense`

### Actions
1. Vérification du stock
2. Décrémentation du stock
3. Marquage de l'ordonnance comme délivrée
4. Enregistrement du paiement

---

## 5. Sortie patient

**Rôle autorisé :** `accueil`, `docteur_general`
**Route :** `POST /sortie/{visit}/cloturer`
**Contrôleur :** `SortieController::close`

### Règles de sortie
1. Vérification de la facture :
   - Si **entièrement payée** → sortie autorisée avec succès
   - Si **impayé < seuil** (configurable, défaut 0) → sortie autorisée avec avertissement `warning`
   - Si **impayé > seuil** → sortie **bloquée** avec message d'erreur
2. Si autorisée : `discharged_at` est enregistré, statut visite inchangé (`Consultation terminée`)

---

## 6. Caisse — Encaissements

**Rôle autorisé :** `caissier`, `docteur_general`
**Route principale :** `GET /caisse`

### Enregistrement d'un paiement
**Route :** `POST /paiements`
**Contrôleur :** `PaymentController::store`

1. Saisie du montant, méthode (Espèces, Carte, Mobile Money, Assurance CNAM, CMU)
2. Création du paiement lié au patient, à la visite, et au caissier
3. Si paiement complet : facture passe en `Payé`

### Clôture de caisse journalière
**Route :** `POST /caisse/cloturer`
**Contrôleur :** `PaymentController::closeDay`

1. Tous les paiements ouverts du caissier pour la date sont clôturés
2. Impossible de modifier un paiement d'un autre caissier

---

## 7. Vue d'ensemble des statuts de visite

| Statut | Déclencheur | Prochaine étape possible |
|--------|-------------|-------------------------|
| `En attente` | Enregistrement | Salle d'attente (constantes) |
| `Prioritaire` | Enregistrement urgence | Salle d'attente (constantes) |
| `En consultation` | Médecin prend le patient | Examens, Ordonnances, Hospitalisation/Observation, Clôture |
| `Demande hospitalisation` | Médecin demande hospitalisation | Approbation/Rejet par aide soignante |
| `Demande observation` | Médecin demande observation | Approbation/Rejet par aide soignante |
| `Hospitalisé` | Approbation hospitalisation | Sortie d'hospitalisation |
| `Observation` | Approbation observation | Sortie d'observation |
| `Retour observation` | Sortie d'observation | Retour en consultation |
| `Consultation terminée` | Clôture consultation | Sortie patient (caisse + docteur général) |
| `Orienté` | Référence externe | Fin de parcours |

---

## 8. Rôles et permissions

| Rôle | Accès principal |
|------|-----------------|
| `accueil` | Enregistrement patients, salle d'attente, sortie |
| `aide_soignante` | Constantes vitales, approbation hospitalisation/observation |
| `medecin` / `pediatre` | Consultation, examens, ordonnances, demandes d'hospitalisation |
| `laborantin` | Traitement des examens |
| `pharmacien` | Pharmacie, dispensation |
| `caissier` | Caisse, encaissements |
| `docteur_general` | Supervision globale, sortie patients, clôture |
| `admin` | Gestion utilisateurs, compagnies d'assurance |

---

## 9. Facturation

- **Création** : automatique lors de l'enregistrement patient
- **Recalcul** : automatique après chaque action (hospitalisation, examen, ordonnance, clôture)
- **Composants** : acte médical + hospitalisation/jour + examens + ordonnances
- **Paiement** : enregistré par le caissier, peut être partiel ou complet
- **Sortie bloquée** si impayé > seuil configurable
