# Audit UX/UI — Clinique Saint Matthieu

Verdict : AMÉLIORATIONS MAJEURES REQUISES

## Résumé exécutif

La Clinique Saint Matthieu dispose d'une base design system solide (16 composants Blade, palette cohérente, Tailwind + Alpine.js) et d'une architecture modulaire bien structurée. Cependant, l'audit révèle des **problèmes bloquants d'accessibilité** (icônes manquantes qui cassent le rendu), des **incohérences d'architecture** (largeur sidebar documentée vs implémentée, pagination non uniformisée), et des **états système incomplets** (pas de skeleton/loading, pas de empty state sur la table d'accueil, pagination fantôme). Le code est fonctionnel mais plusieurs éléments risquent de casser en production.

---

## 1. Design system compliance

### 1.1 Palette et typographie

| Aspect | État |
|--------|------|
| Couleurs brand | Conforme — brand-50 à brand-900 utilisés correctement |
| Couleurs sémantiques (success, warning, danger) | Conforme |
| Typographie (Inter, échelle, graisses) | Conforme — text-2xl/sm/xs utilisés selon le skill |
| Espacements (gap-4, gap-6, px-5 py-3.5) | Largement conforme |
| Border radius (rounded-md, rounded-lg) | Conforme |
| Shadows (shadow-card, shadow-card-hover) | Conforme |

**Note positive** : L'adhésion à la palette et aux espacements est exemplaire sur toutes les vues auditées.

### 1.2 Composants disponibles

| Composant | Utilisation | Observation |
|-----------|-------------|-------------|
| `avatar` | Utilisé systématiquement | Conforme |
| `badge` | Utilisé systématiquement | Conforme, mais pas de dot optionnel exploitée |
| `button` | Utilisé systématiquement | Conforme — variants et tailles respectés |
| `card` | Conteneur principal | Conforme |
| `circuit-card` | Défini mais **jamais utilisé** dans les vues auditées | Composant mort |
| `dropdown` | Header (profil utilisateur) | Conforme |
| `field` | Formulaires | Conforme, mais des `<select>` natifs subsistent |
| `filter-pills` | Accueil, caisse, chambres | Conforme |
| `icon` | Toutes les vues | **PROBLÈME CRITIQUE — voir section CRITICAL** |
| `patient-search` | Accueil, caisse, laboratoire | Conforme |
| `queue-list` | Attente, pharmacie | Conforme |
| `select` | Formulaires | Conforme |
| `slide-over` | Tous les formulaires complexes | Conforme |
| `stat-card` | KPIs tous modules | Conforme |
| `textarea` | Médecins | Conforme |
| `timeline` | Médecin, sortie | Conforme |

### 1.3 Écarts critiques

**Icones manquantes ou hors skill** — Plusieurs vues utilisent des icônes Heroicons qui ne sont PAS dans la liste du skill, ce qui provoquera un rendu vide (SVG non défini) :

| Icône utilisée | Vue | Icône équivalente dans le skill |
|----------------|-----|--------------------------------|
| `briefcase` | app-shell sidebar "Gestion clinique" | Absente — ajouter `briefcase` au skill ou utiliser `document`/`cog` |
| `archive` | caisse dashboard (bouton Historique) | `archive-box` existe, pas `archive` |
| `refresh-cw` | chambres board (bouton Actualiser) | Absente — utiliser `arrow-path` ou `arrow-right` |
| `microscope` | laboratoire dashboard (état vide) | Absente — utiliser `beaker` |
| `information-circle` | laboratoire dashboard (debug info) | Absente — utiliser `bell` ou `exclamation-circle` |
| `trash` | chambres board (suppression) | Absente — utiliser `archive-box` ou ajouter au skill |
| `check` (sans `-circle`) | hospitalisation requests (bouton Approuver) | `check-circle` existe, pas `check` simple |

**Désaccord — Pourquoi.** Chacune de ces icônes manquantes produira un SVG vide (le composant `x-ui.icon` ne trouve pas le chemin correspondant et ne rend rien). Sur les boutons d'action (Approuver, Supprimer, Actualiser), l'absence d'icône + texte uniquement = perte de reconnaissance visuelle immédiate, surtout pour les utilisateurs qui balaient l'interface rapidement.

**Alternative.** Soit étendre la liste du skill avec ces icônes (elles sont toutes dans Heroicons 24x24 outline), soit substituer par des icônes existantes listées dans le skill.

**Décision.** À toi de trancher. Je recommande d'ajouter les 7 icônes manquantes au skill et au composant `icon` — coût minime, gain de cohérence immédiat.

---

## 2. Information architecture

### 2.1 Navigation

La sidebar (`sidebar-nav.blade.php`) est bien structurée avec :
- Filtrage par rôle automatique
- Badge de notification par module
- État actif avec bordure brand-500 + fond brand-50
- Sous-menus pour Hospitalisation et Gestion clinique

**Problème** : La largeur de sidebar est `w-64` (256px) dans le code, mais le skill documente "sidebar fixe 64px". 64px = `w-16`, ce qui serait bien trop étroit pour des labels texte. Le code est correct, la documentation du skill est erronée.

**Problème** : L'item `personnel` a `'roles' => []` (ligne 61 de app-shell) — donc il n'est jamais accessible à personne. Rôle orphelin.

**Problème** : Le header contient une barre de recherche globale (`placeholder="Rechercher..."`) qui n'est connectée à rien. Aucun endpoint, aucun comportement. C'est un élément décoratif qui crée une fausse attente.

### 2.2 Hiérarchie des modules

Les modules suivent un flux logique patient : Accueil → Attente → Médecin → Examens/Ordonnances → Caisse → Sortie. L'ajout d'Hospitalisation, Laboratoire, Pharmacie, Logistique comme services annexes est cohérent.

**Point positif** : Le module Docteur Général propose un onglet "Accès rapide" vers tous les modules — excellente solution pour le rôle de supervision.

---

## 3. User flows

### 3.1 Parcours "Nouveau patient → consultation → paiement → sortie"

| Étape | Module | Action | Évaluation |
|-------|--------|--------|------------|
| 1 | Accueil | "Nouveau patient" via slide-over | ✅ Slide-over bien structuré, recherche patient existant intégrée |
| 2 | Attente | Vue board en temps réel | ✅ Polling 10s, rafraîchissement manuel |
| 3 | Médecin | Prendre le patient, constantes, diagnostic | ✅ Formulaire complet dans card |
| 4 | Caisse | Encaisser | ⚠️ Formulaire dans slide-over, calcul auto du montant |
| 5 | Sortie | Rechercher patient, clôturer | ✅ Vue complète avec timeline |

**Friction identifiée :**

1. **Étape 1 → 2** : Aucun lien visible entre l'enregistrement du patient et la salle d'attente. L'utilisateur doit naviguer manuellement via la sidebar. Un retour visuel "Patient enregistré, redirigé vers la salle d'attente" ou un bouton "Voir en salle d'attente" ferait gagner du temps.

2. **Étape 3** : Le formulaire médecin demande les constantes ET le diagnostic dans le même écran, mais la validation se fait en une seule fois. Si le patient n'a pas ses constantes, on ne peut pas enregistrer le diagnostic séparément.

3. **Étape 4** : Le formulaire d'encaissement est dans un slide-over qui se ferme après soumission (pas de preuve — le code recharge la page). L'utilisateur perd le contexte de la liste.

4. **Étape 4 → 5** : Aucun indicateur de passage au statut "prêt pour sortie". Le patient passe de "payé" à invisible dans le flux jusqu'à ce que l'accueil ou le médecin le marque pour sortie.

### 3.2 Parcours "Hospitalisation"

| Étape | Module | Action | Évaluation |
|-------|--------|--------|------------|
| 1 | Médecin | "Hospitaliser" depuis le dashboard | ⚠️ Bouton danger `Hospitaliser` à côté de "Orienter" et "Clôturer" — 3 actions dans un même groupe, hiérarchie visuelle faible |
| 2 | Hospitalisation | Liste des patients hospitalisés | ✅ Table claire |
| 3 | Demandes d'admission | Aide-soignante valide/rejette | ✅ Cards bien structurées |
| 4 | Chambres | Vue occupation | ✅ Grille visuelle |

**Friction** : La demande d'hospitalisation part du médecin, mais l'approbation est faite par une aide-soignante sur un module séparé. Aucune notification dans le module aide-soignante quand une nouvelle demande arrive — il faut rafraîchir manuellement.

---

## 4. Accessibilité

### 4.1 Contraste WCAG AA

| Élément | Couleurs utilisées | Ratio estimé | Conforme |
|---------|-------------------|--------------|----------|
| Texte principal (ink #16325c sur blanc) | #16325c / #ffffff | ~12:1 | ✅ AA (4.5:1) + AAA |
| Texte secondaire (ink-soft #3e4551 sur blanc) | #3e4551 / #ffffff | ~7.5:1 | ✅ AA + AAA |
| Texte muted (ink-muted #706e6b sur blanc) | #706e6b / #ffffff | ~4.6:1 | ✅ AA juste |
| Bouton primary (blanc sur brand-500) | #ffffff / #0176d3 | ~4.3:1 | ✅ AA |
| Badge danger texte sur fond | #ba0517 / danger-tint | Dépend du tint | ✅ si tint = bg-danger/10 |
| Badge warning sur fond warning/5 | #a35200 / warning/5 | ~3.8:1 estimé | ⚠️ Proche de la limite |

**Attention** : Le badge warning sur fond `bg-warning/5` (opacité 5%) risque de ne pas atteindre le ratio 4.5:1. Vérifier avec un outil de contraste.

### 4.2 Navigation clavier

| Aspect | État |
|--------|------|
| Boutons avec `focus-visible:ring-2` | ✅ Présent dans le design system |
| Skip-to-content link | ❌ Absent |
| Ordre de tabulation logique | ✅ Respecté dans les formulaires |
| Fermeture modale/drawer via Escape | ❌ Non implémenté (seul le clic sur l'overlay ferme le slide-over) |
| Focus trap dans les slide-overs | ❌ Absent — le focus peut partir dans le contenu derrière |
| Rôle ARIA sur les menus dropdown | ⚠️ Le composant dropdown n'a pas de `role="menu"` / `role="menuitem"` |

### 4.3 Cibles tactiles

| Élément | Taille | Conforme |
|---------|--------|----------|
| Boutons `sm` (px-3 py-1.5) | ~30px hauteur | ❌ En dessous de 44px |
| Boutons `md` (px-4 py-2) | ~36px hauteur | ❌ En dessous de 44px |
| Boutons `lg` (px-5 py-2.5) | ~40px hauteur | ⚠️ Encore sous les 44px recommandés |
| Pagination buttons (h-8 w-8) | 32px | ❌ |
| Icon-only buttons (h-9 w-9) | 36px | ❌ |
| Nav items (px-3 py-2.5) | ~36px hauteur | ❌ |

**Désaccord.** Le skill spécifie des tailles de boutons qui produisent des hauteurs de 30-40px, toutes en dessous des 44px requis par WCAG 2.5.5 (Target Size) pour les cibles tactiles.

**Pourquoi.** Sur tablette ou mobile, des cibles de 30-36px obligent l'utilisateur à viser précisément, générant des erreurs de toucher et de la frustration. C'est un problème d'accessibilité documenté.

**Alternative.** Augmenter les tailles dans le composant button :
- `sm` : `text-xs px-4 py-2` (≥ 36px, acceptable)
- `md` : `text-sm px-5 py-2.5` (≥ 44px — conforme)
- `lg` : `text-sm px-6 py-3` (≥ 48px — confortable)

Et ajuster les nav items à `py-3` minimum.

**Décision.** À toi de trancher. Je recommande de modifier le composant button et sidebar-nav pour respecter les 44px.

### 4.4 Lecteur d'écran

| Aspect | État |
|--------|------|
| `lang="fr"` sur `<html>` | ✅ Présent |
| Labels sur inputs | ✅ Le composant `field` génère des `for` / `id` |
| `aria-label` sur icon-only buttons | ✅ Bouton menu mobile, fermeture drawer |
| `aria-live` pour polling attente | ❌ Pas de région live — les lecteurs d'écran n'annoncent pas les changements |
| Alt text sur les icônes décoratives | ⚠️ Icônes décoratives sans `aria-hidden="true"` |
| Structure de titre (h1 → h2 → h3) | ✅ Cohérente sur toutes les vues |

---

## 5. Responsive design

### 5.1 Breakpoints utilisés

| Breakpoint | Usage dans le projet |
|------------|---------------------|
| `< sm` (mobile) | Sidebar en drawer, formulaire empilé |
| `sm:` (640px) | Grilles 2 colonnes, padding augmenté |
| `lg:` (1024px) | Sidebar permanente, grilles 3 colonnes, cache du nom dans le header |
| `xl:` (1280px) | Board attente en 7 colonnes |

### 5.2 Points de friction

**Tableau de la caisse** — 11 colonnes (Patient, Couverture, Sous-total, Assurance, À payer, Statut, Caissier, Service, Montant Total, Mode de paiement, Actions). Sur mobile (< 640px), le scroll horizontal est inévitable. Sur tablette (640-1024px), seules 4-5 colonnes sont visibles simultanément. C'est la limite acceptable, mais le tableau mériterait une version "carte" sur mobile ou un masquage des colonnes secondaires.

**Board salle d'attente** — 7 colonnes sur desktop (`xl:grid-cols-7`), mais chaque colonne contient des cards patient qui deviennent très étroites sur écran < 1440px. Sur un écran 13" (1280px), chaque colonne fait ~150px, à la limite du lisible.

**Chambres** — Grille de lits `sm:grid-cols-2 lg:grid-cols-3` fonctionne bien, mais les beds individuels (avec avatar patient, nom, dates) deviennent comprimés sur tablette.

---

## 6. Consistency

### 6.1 Langue française

| Trouvé | Conforme ? |
|--------|-----------|
| "Nouveau patient" / "Nouvelle admission" | ✅ Accord correct (patient masculin, admission féminin) |
| "Nouvel examen" / "Nouvel encaissement" | ✅ Accord correct (examen masculin, encaissement masculin) |
| "Nouvelle ordonnance" / "Nouvelle commande" | ✅ |
| "Payees" (caisse KPI) | ❌ Devrait être "Payées" (accent) |
| "Factures reglees" (caisse KPI) | ❌ Devrait être "réglées" (accent) |
| "Cloturee" (caisse KPI label) | ❌ Devrait être "Clôturée" |
| "Cloturer" (boutons) | ❌ Devrait être "Clôturer" |
| "Journee cloturee" | ❌ Devrait être "Journée clôturée" |
| "Recevable" dans le code | ⚠️ Orthographe variable selon les endroits |

**Désaccord.** Les accents manquants sur "Payees", "reglees", "Cloturee" ne sont pas des détails — sur un produit professionnel de santé, des textes fautifs réduisent la crédibilité et la confiance.

**Alternative.** Audit complet de la copie française via spell-check intégré, ou extraction des chaînes dans un fichier de traduction dédié.

### 6.2 Composants

Le composant `circuit-card` est défini dans le design system mais **jamais utilisé** dans les vues auditées. Il a été créé pour "items de parcours patient" mais le parcours est implémenté différemment dans chaque module. Soit il faut l'utiliser, soit il faut le supprimer pour réduire la dette technique.

### 6.3 Pagination

| Vue | Implémentation |
|-----|---------------|
| caisse/sessions | ✅ Laravel pagination (`$sessions->links()`) |
| accueil | ❌ Boutons disabled, aucune pagination |
| caisse/dashboard | ❌ Aucune pagination — tous les encaissements du jour chargés d'un coup |
| medecin | ❌ Pas de pagination (file d'attente) |
| laboratoire | ❌ Pas de pagination |
| logistique | ❌ Pas de pagination |

**Sur le dashboard caisse**, si une journée chargée génère plus de 50-100 encaissements, le chargement initial devient lent. Le tableau charge toutes les données d'un coup.

### 6.4 Incohérences de patterns

| Pattern | Accueil | Caisse | Médecin | Labo |
|---------|---------|--------|---------|------|
| KPIs en haut | stat-card x4 | stat-card x4 | ❌ Absent | stat-card x3 |
| Formulaire création | slide-over | slide-over | slide-over x3 | slide-over |
| Tableau principal | card + table | card + table | ❌ (queue-list) | card + table |
| Actions inline | Bouton secondaire | Boutons mixtes (a href + form + onclick) | Boutons secondaires + forms | Bouton secondaire |

**Problème majeur** : Le dashboard médecin n'a pas de KPIs. C'est le seul module principal sans KPI — l'utilisateur doit consulter la file d'attente sans contexte quantitatif (nombre de patients vus dans la journée, temps moyen de consultation, etc.).

---

## 7. Error / empty / loading states

### 7.1 États vides

| Vue | État vide | Évaluation |
|-----|-----------|------------|
| accueil | ❌ Pas de `@empty` sur la table patients. Si 0 arrivée, tableau vide avec headers uniquement. | **MAJOR** |
| attente | ✅ Cards "Aucun patient" par colonne | ✅ |
| caisse | ✅ "Aucun encaissement enregistré..." | ✅ |
| medecin | ✅ Card centrée "Aucun patient en attente" | ✅ |
| laboratoire | ✅ État vide + debug info conditionnel | ✅ |
| hospitalisation | ✅ "Aucune demande en attente" | ✅ |
| logistique | ⚠️ Tableaux sans `@empty` | **MAJOR** |
| pharmacie | ❌ Pas d'état vide visible dans le code | **MAJOR** |
| chambres | ✅ "Aucune chambre trouvée" | ✅ |
| sortie | ✅ "Aucun patient prêt pour la sortie" | ✅ |

### 7.2 États de chargement

| Vue | Loading state | Évaluation |
|-----|---------------|------------|
| attente | ✅ Spinner animé sur le bouton Rafraîchir | ✅ |
| Toutes les autres | ❌ Aucun skeleton, spinner, ou indicateur de chargement | **CRITICAL** |

**Désaccord.** L'absence totale d'états de chargement sur toutes les vues sauf une est un problème d'UX fondamental. Quand un caissier enregistre un paiement ou qu'un médecin soumet une ordonnance, l'écran freeze jusqu'au retour serveur. L'utilisateur ne sait pas si l'action est en cours, a échoué, ou a réussi.

### 7.3 États d'erreur

| Vue | Erreur réseau | Erreur métier | Évaluation |
|-----|---------------|---------------|------------|
| caisse | ⚠️ `alert()` + `location.reload()` | ⚠️ `alert()` | Non-accessible, non-informative |
| attente | ⚠️ `console.warn` seulement | ❌ Aucun message | Silencieux |
| accueil | ❌ Aucune gestion | ✅ Laravel validation errors | Partiel |
| medecin | ❌ Aucune gestion | ✅ Laravel validation errors | Partiel |
| Global (app-shell) | ✅ Flash messages (success/error) | ✅ | Bien |

**Désaccord.** Utiliser `alert()` et `confirm()` natifs est un anti-pattern UX. Ces boîtes de dialogue bloquent l'interface, n'ont pas de style cohérent, et sont mal supportées par les lecteurs d'écran.

**Alternative.** Un système de toast/notification Alpine.js (déjà Alpine est chargé) ou utiliser les flash messages Laravel pour les retours positifs, et afficher les erreurs inline dans les composants.

---

## 8. Performance perception

### 8.1 Chargement initial

Pas de skeleton screens nulle part. Les tableaux apparaissent "d'un coup" après le rendu serveur. Sur une journée chargée en caisse (>100 encaissements), le tableau de la caisse peut prendre plusieurs secondes à s'afficher.

### 8.2 Polling

Le board d'attente utilise un polling de 10 secondes avec fetch JSON. L'app-shell fetche les badges toutes les 30 secondes. Aucune optimisation (debounce, stale-while-revalidate) n'est en place. Sur un réseau lent, ces requêtes simultanées peuvent se chevaucher.

### 8.3 Rechargements complets

Le bouton "Encaisser" dans le dashboard caisse fait `location.reload()` après succès. Le bouton de suppression de chambre aussi. Ces rechargements complets sont coûteux et font perdre le contexte (scroll position, expanded states, etc.).

---

## 9. Architecture recommendations

### 9.1 Compléter le design system

1. **Ajouter les 7 icônes manquantes** au composant `x-ui.icon` : `briefcase`, `archive`, `refresh-cw`, `microscope`, `information-circle`, `trash`, `check`
2. **Corriger la largeur sidebar** dans la doc : w-64 (256px), pas 64px
3. **Ajouter un composant `toast`** pour les notifications non-bloquantes
4. **Ajouter un variant `xs`** au composant button pour respecter les 44px sur mobile (ou augmenter `sm` à px-4 py-2)

### 9.2 Standardiser les états

1. **Loading** : Ajouter un composant `skeleton` pour les tableaux et les KPIs
2. **Empty** : Corriger la table accueil (ajouter `@empty`), ajouter `@empty` aux tableaux logistique et pharmacie
3. **Error** : Remplacer tous les `alert()` par un système de toast ou des messages inline

### 9.3 Navigation et recherche

1. **Connecter la barre de recherche globale** ou la retirer — un placeholder sans fonction crée de la frustration
2. **Corriger l'item `personnel`** — soit ajouter les rôles autorisés, soit le masquer
3. **Ajouter un breadcrumb** sur les vues de détail (fiche patient, détail session caisse)

### 9.4 Pagination

1. **Standardiser sur Laravel pagination** pour tous les tableaux susceptibles de dépasser 20-30 lignes
2. **Ajouter un lazy-load / infinite scroll** optionnel pour le board d'attente sur mobile

---

## 10. Design system adherence score

| Catégorie | Score | Détail |
|-----------|-------|--------|
| Couleurs | 9/10 | Conforme, attention au warning tint |
| Typographie | 9/10 | Conforme, échelle respectée |
| Espacements | 8/10 | Largement conforme, quelques incohérences mineures |
| Composants | 7/10 | 16 composants définis, 1 mort (circuit-card), 2 sous-utilisés (field, select) |
| États | 4/10 | États vides présents sur 60% des vues, loading quasi absent, error states non-accessibles |
| Accessibilité | 5/10 | Contraste OK, mais cibles tactiles < 44px, pas de focus trap, aria-live manquant |
| Responsive | 7/10 | Mobile-first respecté, mais tableaux larges sans version mobile |
| Français | 6/10 | Accents manquants, quelques incohérences |
| Icônes | 4/10 | 7 icônes utilisées hors de la liste du skill |

**Score global : 6.6/10**

---

## 11. Detailed findings

### CRITICAL

| # | Titre | Localisation | Description | Recommandation |
|---|-------|-------------|-------------|----------------|
| C1 | Icônes Heroicons manquantes causent un rendu vide | app-shell (ligne 51), caisse/dashboard (ligne 148), chambres (lignes 14, 148), laboratoire (ligne 46), hospitalisation/requests (ligne 64) | 7 icônes utilisées (`briefcase`, `archive`, `refresh-cw`, `microscope`, `information-circle`, `trash`, `check`) ne sont pas définies dans le composant `icon` ni listées dans le skill. Le SVG rendu est vide, ces boutons/indicateurs n'ont plus d'icône. | Ajouter les chemins SVG correspondants au composant `icon` et mettre à jour le skill. |
| C2 | Absence totale d'états de chargement | Toutes les vues sauf attente | Aucun skeleton, spinner, ou indicateur de chargement sur les soumissions de formulaires, les chargements de tableaux, ou les actions inline. L'utilisateur ne sait pas si l'action est en cours. | Ajouter un composant `skeleton` au design system. Appliquer un spinner sur les boutons de soumission pendant le chargement. |
| C3 | Cibles tactiles sous les 44px | Tous les composants interactifs | Les boutons sm/md/lg et les nav items ont des hauteurs de 30-40px, en dessous du seuil WCAG 2.5.5 de 44px. La pagination (h-8 = 32px) est également hors norme. | Augmenter les tailles dans le composant button : sm → px-4 py-2 (36px), md → px-5 py-2.5 (40px), lg → px-6 py-3 (44px). Idem pour nav items. |
| C4 | Pagination fantôme sur accueil | accueil/dashboard lignes 203-211 | Les boutons de pagination sont `disabled` sans lien vers des pages. Cela donne l'impression que la pagination existe mais est cassée. | Retirer les boutons de pagination si non fonctionnels, ou implémenter Laravel pagination. |
| C5 | `alert()` et `confirm()` natifs | caisse/dashboard (lignes 314, 318, 348), chambres (lignes 121, 130) | Les confirmations de suppression et d'encaissement utilisent `confirm()` qui est bloquant, non-stylisable, et mal supporté par les lecteurs d'écran. Les alertes d'erreur sont également non-accessibles. | Remplacer par des modales Alpine.js ou des toasts inline. |

### MAJOR

| # | Titre | Localisation | Description | Recommandation |
|---|-------|-------------|-------------|----------------|
| M1 | Tableau accueil sans état vide | accueil/dashboard — absence de `@empty` | Si 0 arrivée aujourd'hui, le tableau affiche uniquement les headers vides. Aucun message, aucune illustration. | Ajouter un bloc `@empty` avec message "Aucune arrivée enregistrée aujourd'hui" + illustration. |
| M2 | Barre de recherche globale non fonctionnelle | app-shell ligne 157 | Input "Rechercher..." dans le header n'est connecté à aucun endpoint ni comportement. | Connecter à une recherche globale cross-modules, ou retirer l'input. |
| M3 | `location.reload()` après actions | caisse/dashboard ligne 341, chambres ligne 139 | Rechargement complet de la page après encaissement ou suppression, perd le contexte (scroll, états). | Utiliser Alpine.js pour mettre à jour le DOM localement (retirer la ligne du tableau, mettre à jour les KPIs). |
| M4 | Badge `bg-warning/5` — contraste potentiellement insuffisant | app-shell ligne 119, accueil ligne 126 | Fond warning à 5% d'opacité sur blanc. Le ratio de contraste du texte warning (#a35200) sur ce fond risque d'être sous les 4.5:1. | Vérifier avec un outil de contraste. Si < 4.5:1, augmenter l'opacité à 10% (`bg-warning/10`). |
| M5 | Dashboard médecin sans KPIs | medecin/dashboard — absence de stat-cards | Le seul module principal sans KPI. Le médecin doit consulter la file d'attente sans vue quantitative (patients vus, temps moyen, etc.). | Ajouter 3-4 stat-cards : patients vus, en attente, urgences, temps moyen. |
| M6 | Item `personnel` orphelin | app-shell ligne 61 | `'roles' => []` → jamais accessible. Soit c'est un bug, soit c'est une feature à implémenter. | Ajouter les rôles autorisés ou supprimer l'item. |
| M7 | Composant `circuit-card` mort | resources/views/components/ui/circuit-card.blade.php | Défini dans le design system mais jamais utilisé. Le parcours patient est implémenté via des patterns différents dans chaque module. | Utiliser `circuit-card` dans le parcours patient, ou retirer du design system. |
| M8 | 3 actions simultanées sans hiérarchie | medecin/dashboard ligne 223 | "Orienter", "Hospitaliser" (danger), "Clôturer" sont côte à côte. Aucune action principale n'émerge visuellement. | Grouper : action principale "Clôturer" en primary, actions secondaires en ghost/secondary. "Hospitaliser" mérite un pattern de confirmation spécifique. |
| M9 | `hover:bg-danger/5` sur les lignes payées | caisse/dashboard ligne 223 | Les encaissements payés sont surlignés en fond danger/5, ce qui suggère un problème. Le paid est un succès, pas un danger. | Remplacer par `bg-success/5` ou retirer le surlignage. |
| M10 | Placeholder "À connecter" en production | docteurgeneral/dashboard lignes 50, 227-239 | Des KPIs et des métriques affichent "À connecter" / "Nécessite la planification" directement dans l'interface. L'utilisateur final voit des données manquantes. | Masquer les sections non connectées, ou afficher un message d'information générique ("Données en cours de consolidation"). |

### MINOR

| # | Titre | Localisation | Description | Recommandation |
|---|-------|-------------|-------------|----------------|
| m1 | Accents manquants dans la copie | caisse/dashboard (Payees, reglees, cloturee, cloturer) | Texte en français sans accents. Sur un produit professionnel de santé, cela nuit à la crédibilité. | Audit complet de la copie, ajout des accents manquants. |
| m2 | `avatar` avec `size="xs"` | chambres/board ligne 106 | `xs` n'est pas une taille valide du composant avatar (sm, md, lg uniquement). | Ajouter `xs` au composant, ou utiliser `sm` avec ajustement. |
| m3 | `select` natif sans composant dédié | accueil/dashboard ligne 87 (acte demandé), hospitalisation/requests ligne 49, chambres board | Ces `<select>` utilisent des classes Tailwind directes au lieu du composant `x-ui.select`. | Migrer vers `x-ui.select` pour cohérence. |
| m4 | Polling badge inconditionnel | app-shell lignes 103-109 | Le polling des badges (`/api/badges`) tourne toutes les 30s même si l'utilisateur ne consulte pas le dashboard. | Déclencher le polling uniquement quand la sidebar est visible/ouverte. |
| m5 | Boutons disabled sans aria-disabled | caisse/sessions/index (pagination) | Les boutons disabled n'ont pas `aria-disabled="true"`, donc les lecteurs d'écran ne savent pas qu'ils sont désactivés. | Ajouter `aria-disabled` et retirer du taborder (`tabindex="-1"`). |
| m6 | Boutons d'action avec onclick inline | caisse/dashboard ligne 278 (`handleEncaisser`) | Le pattern `onclick="handleEncaisser(this)"` est du JavaScript inline qui casse la séparation des responsabilités. | Migrer vers un composant Alpine.js dédié ou un formulaire. |
| m7 | Debug info visible en production | laboratoire/dashboard lignes 42-63 | Le bloc `$showDebugInfo` affiche des messages techniques ("Aucun examen trouvé pour la période sélectionnée") destinés aux développeurs, pas aux utilisateurs finaux. | Retirer le bloc debug en production, ou le gérer via un flag d'environnement. |
| m8 | Incohérence de gap entre sections | Plusieurs vues | Certaines vues utilisent `gap-6` entre sections principales (attente, docteurgeneral), d'autres `gap-4` (accueil, caisse). | Standardiser sur `gap-6` pour les sections majeures, `gap-4` pour les cartes à l'intérieur. |

### POSITIVE

| # | Titre | Localisation | Description |
|---|-------|-------------|-------------|
| P1 | Adhésion excellente à la palette et à la typographie | Toutes les vues | Les couleurs, graisses, tailles de texte respectent strictement le design system. |
| P2 | Composants cohérents | Toutes les vues | `stat-card`, `badge`, `avatar`, `card`, `button` utilisés de manière homogène. |
| P3 | États vides bien gérés | attente, caisse, hospitalisation, logistique, sortie, chambres | La plupart des listes ont des états vides informatifs avec icône + texte. |
| P4 | Slide-over systématique pour les formulaires complexes | Tous les modules | Pattern uniforme pour créer/éditer, avec fermeture et retour visuel cohérent. |
| P5 | Badges de statut bien exploités | Toutes les vues | Utilisation correcte des tons (danger pour urgent, success pour payé, warning pour alerte). |
| P6 | 403 personnalisé et accessible | errors/403.blade.php | Page d'erreur bien conçue avec brand, message clair, bouton de retour, informations sur le rôle. |
| P7 | Header avec dropdown profil | app-shell | Dropdown Alpine.js fluide, avec paramètres et déconnexion. Transitions propres. |
| P8 | Alpine.js bien exploité | app-shell, attente, accueil, sortie | Logique réactive bien isolée dans les fonctions Alpine (`attenteBoard`, `patientForm`, etc.). |
| P9 | Mobile-first respecté | Toutes les vues | Sidebar en drawer, grilles empilées, formulaires en colonne unique sur mobile. |
| P10 | Patient-search component bien fait | accueil, caisse, laboratoire | Combo-box avec recherche par code/nom, sélection clavier, dispatch d'événements Alpine. |

---

## Prochaine action

1. **Corriger les 7 icônes manquantes** dans le composant `x-ui.icon` et mettre à jour le skill — c'est le blocant le plus urgent.
2. **Ajouter les états de chargement** (skeleton/spinner) sur les formulaires et tableaux — prioriser caisse, médecin, accueil.
3. **Corriger les cibles tactiles** dans le composant `button` (passer md à ≥ 44px).
4. **Corriger l'état vide** du tableau accueil et ajouter les `@empty` manquants.
5. **Auditer et corriger la copie française** (accents manquants).

Pour `fullstack-devops` :

```
Écran(s) : Design system (composant icon, button), toutes les vues
Pour : fullstack-devops
Objectif utilisateur : Interface sans icônes cassées, formulaires avec feedback de chargement, cibles tactiles conformes
Fichiers de spec : docs/design/ux-audit-report.md (ce document)
Composants du design system concernés : icon, button, stat-card, card, badge, field, select, slide-over, skeleton (nouveau)
Contraintes techniques connues : Laravel 11 + Blade + Tailwind + Alpine.js, pas de changement d'architecture
Attendu en sortie : Icônes corrigées, états de chargement, tailles de boutons ≥ 44px, états vides complets, copie française corrigée
```
