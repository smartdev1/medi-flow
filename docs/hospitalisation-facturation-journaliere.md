# Facturation journalière des hospitalisations

## État actuel

### Configuration

| Paramètre | Valeur | Fichier |
|---|---|---|
| Tarif journalier unique | `10 000 FCFA / jour` | `config/clinic.php` → `daily_hospitalization_rate` |

### Calcul existant dans `Invoice::recalculate()`

```php
foreach ($admissions as $admission) {
    $endDate   = $admission->discharged_at ?? now();
    $days      = $admission->admitted_at->diffInDays($endDate) + 1;
    $dailyRate = config('clinic.daily_hospitalization_rate', 10_000);
    $admissionTotal = $days * $dailyRate;
}
```

### Confirmé par le test E2E

| Composant | Montant |
|---|---|
| Consultation générale | 5 000 FCFA |
| Hospitalisation (1 jour, Chambre 10) | 10 000 FCFA |
| **Total facture #9** | **15 000 FCFA** |

---

## Problèmes identifiés

### 1. Tarif unique quel que soit le type de chambre

**Impact** : Une chambre VIP est facturée au même tarif qu'une chambre commune.

### 2. Arrondi "jour complet" systématique

`diffInDays + 1` comptabilise toujours un jour complet, même pour :
- Une hospitalisation de 2 heures (ex: observation courte)
- Une sortie le même jour

**Impact** : Sur-facturation pour les séjours courts, pas de prorata horaire.

### 3. Aucune traçabilité par jour

La facture contient un seul montant d'hospitalisation. Impossible de voir :
- Combien de jours facturés
- Quel tarif appliqué par jour
- Historique jour par jour

---

## Recommandations

### Priorité 1 — Tarif par type de chambre

Ajouter un champ `daily_rate` sur le modèle `Room`, avec des valeurs par défaut selon le service :

| Type de chambre | Tarif suggéré |
|---|---|
| Commune | 10 000 FCFA |
| Individuelle | 18 000 FCFA |
| VIP | 30 000 FCFA |

### Priorité 2 — Calcul précis des jours

Remplacer `diffInDays + 1` par un calcul au prorata :

- Option A : **Prorata horaire** avec seuil minimum (ex: < 4h = 50%, < 2h = gratuit)
- Option B : **Arrondi à la demi-journée** (matin / après-midi / nuit)
- Option C : **Jour plein**, mais sortie avant 12h = non facturé ce jour

### Priorité 3 — Lignes de facturation journalières

Générer une ligne par jour d'hospitalisation pour :
- La traçabilité
- Les audits
- La gestion des conflits avec les assurances

### Priorité 4 — Lien Admission ↔ Facture

Enrichir `Invoice::recalculate()` pour utiliser le tarif de la chambre assignée plutôt que le tarif global.

---

---

## Problème connexe — Catégorisation financière en supervision

### Constat

`Invoice::recalculate()` agrège tous les montants (consultation + hospitalisation + examens) dans un seul champ `subtotal` sans catégorie.

`ClinicMetrics::finance()` catégorise par `str_contains` sur `Payment.service` (label de l'acte, pas sa catégorie). L'hospitalisation ne crée pas de `Payment` — elle est calculée dans la facture, donc invisible dans la supervision par service.

### Conséquence

Dans la vue Supervision → Finance, la section "CA par service" ne reflète pas la réalité :
- Les hospitalisations n'apparaissent pas comme une catégorie propre
- Les examens labo/imagerie sont regroupés sous "Consultation"
- Impossible de savoir combien rapporte la pharmacie vs la labo vs l'hospitalisation

### Solution proposée

1. Ajouter une colonne JSON `breakdown` sur `invoices`
2. `Invoice::recalculate()` remplit le breakdown par catégorie (Consultation, Hospitalisation, Laboratoire, Imagerie, Pharmacie, etc.)
3. `ClinicMetrics::finance()` agrège depuis les breakdowns au lieu de parser des chaînes

---

## Livré

- [x] `type` et `daily_rate` sur `rooms` table
- [x] Table `admission_day_lines` avec une ligne par jour par admission
- [x] Modèle `AdmissionDayLine` + factory
- [x] `Admission::generateDayLines()` idempotent, appelé au discharge
- [x] `Admission::discharge()` recalcule les invoices après sortie
- [x] `Invoice::recalculate()` utilise `room->daily_rate` via `dayLines->sum('amount')`
- [x] `InvoiceController::show()` timeline par jour
- [x] `InvoiceController::pay()` service string composite avec "Hospitalisation (N jour(s))"
- [x] `config/clinic.php` sans `daily_hospitalization_rate`
- [x] `ClinicSeeder` avec `type` et `daily_rate` selon capacité
- [x] Commande `backfill:admission-day-lines`
- [x] Catégorisation hospitalisation en supervision fonctionne (via Payment.service)
- [x] 7 bugs QA corrigés et reverifiés
