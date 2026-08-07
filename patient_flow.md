# Parcours Patient dans l'application

## Schéma global

```text
Accueil -> Consultation -> Examen / Orientation -> Pharmacie -> Paiement -> Sortie
           ↑           ↓            ↓
           |           |            |
        Hospitalisation -------------- 
```

## 1. Arrivée à l’accueil

- Le patient se présente à l’accueil.
- Le personnel :
  - crée un nouveau `Patient` ou sélectionne un patient existant
  - crée une `Visit` avec :
    - `arrived_at`
    - `orientation` (`Médecin generaliste`, `Pédiatrie`, `Urgence`, `Laboratoire`)
    - `reason`
    - `urgent`
    - `status` (`En attente`, `Prioritaire`, etc.)

## 2. Tableau de bord / file d’attente

- Le patient apparaît dans :
  - `AccueilController` : dashboard des arrivées du jour
  - `AttenteController` : circuit des patients
- Le patient est suivi par étapes :
  - Accueil
  - Consultation
  - Hospitalisation
  - Laboratoire
  - Retour médecin
  - Pharmacie
  - Sortie

## 3. Consultation médicale

- Le médecin ouvre `/medecin`
- L’application sélectionne une `Visit` active et crée ou récupère une `Consultation`
- Le médecin enregistre :
  - constantes vitales
  - notes médicales
  - orientation vers examens, pharmacie, hospitalisation ou autre service

## 4. Examen de laboratoire

- Si nécessaire, le médecin crée un `Exam`
- Le laboratoire traite l’examen via `/laboratoire`
- Statuts possibles :
  - `En attente d’échantillon`
  - `En cours`
  - `Prêt`
- Quand l’examen est prêt, le patient peut retourner au médecin

## 5. Prescription & pharmacie

- Le médecin crée une `Prescription`
- La pharmacie voit l’ordonnance et peut la délivrer
- Si le stock est insuffisant, la prescription passe en `Rupture partielle`
- Lors de la délivrance, un `Payment` est généré

## 6. Paiement

- Le caissier enregistre les paiements dans `/caisse`
- Paiement lié à :
  - `patient_id`
  - `visit_id`
  - `service`
- Le caissier peut :
  - marquer un paiement comme `Payé`
  - générer un reçu PDF
  - clôturer la caisse du jour

## 7. Hospitalisation (optionnel)

- Si le patient doit rester, l’admission est créée :
  - chambre disponible
  - lit attribué
  - statut `En observation`
- La sortie d’hospitalisation est ensuite déclenchée par l’admission

## 8. Sortie du patient

- La sortie se fait sur la `Visit` active
- `discharged_at` est renseigné
- Le patient est retiré du circuit actif

## Résumé du parcours

1. `Accueil` : enregistrement et orientation
2. `Consultation` : examen par le médecin
3. `Examens` / `Orientation` : laboratoires ou services spécialisés
4. `Pharmacie` : délivrance d’ordonnance
5. `Paiement` : encaissement et reçu
6. `Hospitalisation` (si besoin)
7. `Sortie` : clôture de la visite

> L’application suit le patient de l’entrée jusqu’à la sortie, en gardant l’état visible à chaque étape.
