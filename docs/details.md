J'ai maintenant un panorama complet. Voici mon audit détaillé.

---

## 🔍 Audit de conformité : code vs spécifications

### ✅ Ce qui est **correctement implémenté**

| Spécification | Implémentation | Conforme |
|---|---|---|
| 7 rôles métier (8 avec Admin) | `app/Enums/Role.php` avec 8 cas | ✅ |
| RBAC par middleware | `EnsureUserHasRole` sur toutes les routes | ✅ |
| Routes cloisonnées par rôle | `routes/web.php` groupées par `role:xxx` | ✅ |
| Admin a visibilité supervision | Code du middleware : `role === Role::Admin → autorisé partout` | ✅ |
| Code patient unique | `Patient::generateCode()` → `CSM-XXXX` | ✅ |
| Médecin : ne voit que patients orientés | `MedecinController` : `$orientations = match($doctor->role)` | ✅ |
| Pédiatre : uniquement Pédiatrie | `match (...) Pediatre => ['Pédiatrie']` | ✅ |
| Technicien labo : pas de diagnostic | Vue ne charge que `Exam`, pas `Consultation.notes` | ✅ |
| Pharmacien : pas de diagnostic | `PharmacieController` charge `items.medication` uniquement | ✅ |
| Caissier : pas de données médicales | `CaisseController` charge `patient` + `payment` | ✅ |
| Caissier voit types couverture | `Patient::coverage` exposé dans dashboard | ✅ |
| Ordonnance modifiable uniquement par médecin | `PrescriptionController::store` → `role:medecin,pediatre` | ✅ |
| Pharmacien ne modifie pas ordonnance | `dispense` n'altère que le statut, pas le contenu | ✅ |
| Garde médical configurable | `OnCallShift` + `PersonnelController` | ✅ |
| Consultation modifiable tant que non clôturée | `Consultation::close()` + `update()` | ✅ |
| Activité tracée (created_by, registered_by…) | `visits.registered_by`, `consultations.doctor_id`, `prescriptions.prescribed_by` | ✅ |
| Clôture de visite | `SortieController::close` → `discharged_at` | ✅ |
| Hospitalisation avec chambres | `Admission` + `Room` + contrôle `occupied_count` | ✅ |

### ⚠️ **Non-conformités ou failles identifiées**

#### 🔴 Critiques (contreviennent au document `gestion roles perimetre activite.md`)

1. **Caisse peut créer un paiement sans visite active** ⚠️
   - Le contrôleur `PaymentController::store` accepte **n'importe quel patient** (`Patient::findOrFail($data['patient_id'])`)
   - Le document dit que le caissier agit **uniquement sur des patients avec visite active**
   - Manque un contrôle `if (!$patient->visits()->whereNull('discharged_at')->exists()) → abort`

2. **Caissier peut saisir un statut "En attente" manuellement** ❌
   - `PaymentController::store` permet de passer `status: 'En attente'` ou `Partiellement payé` via le formulaire
   - Le document dit : "le caissier **enregistre** un paiement" — pas qu'il le déclare en attente
   - Risque : un caissier peut valider partiellement puis "perdre" la trace

3. **Labo : pas de filtrage par patient affecté** ⚠️
   - `LaboratoireController` charge **tous les examens** des 30 derniers jours
   - Le document dit : "ne voit pas les examens d'un patient qui ne lui a pas été confié"
   - Le système n'a **pas de notion d'affectation** au technicien (pas de champ `assigned_lab_user_id` sur `Exam`)
   - Tous les techniciens voient toutes les analyses

4. **Pharmacien peut délivrer sans paiement encaissé** ❌
   - `PrescriptionController::dispense` exige `hasSufficientStock()` mais **ne vérifie pas** que le paiement a été fait à la caisse
   - Le document dit explicitement : "Vérifier que le paiement a bien été enregistré à la caisse"
   - Le `Payment` est créé **après** la délivrance (ligne 71-78), pas vérifié avant

5. **Pédiatre peut théoriquement voir des patients Médecin via l'admin** ⚠️
   - `MedecinController` : `default => ['Médecin généraliste', 'Urgence', 'Pédiatrie']` pour les admins
   - Correct pour l'admin (supervision), mais l'orientation est dérivée du **rôle**, pas d'une **affectation explicite patient → médecin**
   - Le document dit : "Un médecin ne voit en priorité que les patients qui lui sont attribués"
   - **Aucune affectation médecin↔patient** dans le modèle (le médecin voit **tous** les patients de son orientation)

#### 🟡 Modérées (écart aux bonnes pratiques)

6. **Examen de labo sans notion de "confié à un technicien"**
   - Le workflow décrit un `assigned_lab_user_id` ou similaire pour respecter "patients avec examen qui lui est confié"
   - Manque ce champ dans la migration `create_exams_table`

7. **Le `role` est une `string` dans la BDD mais un `Enum` côté PHP**
   - Cohérent (cast `Role::class`), mais aucun garde-fou au niveau BDD (CHECK constraint)
   - Risque mineur si quelqu'un insère directement un rôle invalide

8. **Pas de gestion des "remplacements"** (mentionné dans le document comme "non couvert")
   - Le document dit : "Ces éléments pourront faire l'objet d'un travail complémentaire"
   - C'est documenté comme hors-périmètre, donc acceptable, mais à garder en tête

9. **Pas d'interface de réinitialisation de mot de passe** côté Admin
   - Le document dit : "Réinitialiser un mot de passe oublié"
   - `PersonnelController` ne contient pas cette action
   - `toggleActive` existe, mais pas `resetPassword`

10. **Pas de consultation des journaux d'activité** pour l'admin
    - Le document dit : "Consulter les journaux d'activité"
    - Aucun modèle `AuditLog` / `Activity` n'existe
    - On a les colonnes `created_by`, mais pas de table d'audit dédiée

11. **Pas de signature `prescribed_by` / `dispensed_by` contrôlée**
    - Migration `add_prescribing_and_dispensing_to_prescriptions` ajoute les colonnes, OK
    - Mais le code `PrescriptionController::store` ne récupère que `prescribed_by` sans contrôle
    - Le pharmacien peut (techniquement) être `dispensed_by` ET avoir accès à tout le flux

#### 🟢 Mineurs (écarts stylistiques ou risques faibles)

12. **Caisse : le filtrage des paiements est fait en PHP** (filter() après `latest()->get()`)
    - Devrait être en SQL pour la performance — `whereHas('visit', fn($q) => $q->active())`
    - Idem pour `LaboratoireController` et `CaisseController`

13. **Plusieurs contrôleurs dupliquent le bloc "user info"**
    - `$userName = $user?->name; $userRole = $user?->role?->label(); $userInitials = $user?->initials ?? $this->initialsFor($userName);`
    - Pourrait être centralisé via un `ViewComposer` ou une méthode du `User`

14. **Le middleware `EnsureUserHasRole` ne journalise pas les accès refusés**
    - En cas de tentative d'accès à une URL non autorisée, on a juste un `abort(403)`
    - Le document demande "Toute action reste tracée" — y compris les tentatives refusées

15. **Présence de fichiers backup** à la racine des contrôleurs et des vues
    - `AccueilController_backup.php`, `chambres/board_backup.blade.php`
    - Pollution du code, à nettoyer

16. **L'admin n'a pas d'interface de consultation des données médicales**
    - Le document dit : "l'ensemble des données de la clinique" pour la supervision
    - L'admin peut techniquement **accéder** à toutes les routes (middleware), mais il n'y a pas de dashboard admin dédié listant les consultations, paiements, etc.

### 📊 Synthèse visuelle

```
Conformité fonctionnelle :     ██████████░░  ~78%
Sécurité RBAC :                █████████░░░  ~80%
Traçabilité / Audit :          █████░░░░░░░  ~50%
Gestion des exceptions :       ███░░░░░░░░░  ~30% (rares cas spéciaux documentés comme hors-périmètre)
```

### 🎯 Top 5 actions prioritaires

1. **Ajouter contrôle paiement pour délivrance pharma** (fausse la sécurité financière)
2. **Filtrer les examens au labo par technicien affecté** (ajouter `assigned_to` sur `Exam`)
3. **Refuser la création de paiement pour patient sans visite active** (cohérence parcours)
4. **Créer table `audit_logs` + journaliser** les actions sensibles (conforme au document)
5. **Implémenter la réinitialisation de mot de passe par l'admin** + **dashboard admin de supervision**