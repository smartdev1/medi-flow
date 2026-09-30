# ui-ux-pro-max

Skill de référence pour la conception UX/UI du projet **Clinique Saint Matthieu**.

## Stack

- Laravel 11 + Blade + Tailwind CSS + Alpine.js + Vite
- Fonts : Inter (primary), Figtree (fallback) — chargées via Bunny.net CDN
- Icônes : Heroicons 24x24 outline (stroke-width="1.5"), SVG inline via composant `x-ui.icon`
- Pas de dark mode — thème clair uniquement
- UI en français

## Composants disponibles

Tous dans `resources/views/components/ui/` :

| Composant | Usage |
|-----------|-------|
| `avatar` | Initiales utilisateur, 5 tons (brand, success, warning, danger, neutral), 3 tailles (sm, md, lg) |
| `badge` | Indicateur de statut compact, 5 tons, dot optionnel |
| `button` | Action principale, 4 variantes (primary, secondary, ghost, danger), 3 tailles (sm, md, lg) |
| `card` | Conteneur blanc avec bordure et ombre, padding optionnel |
| `circuit-card` | Carte avec bordure d'accent gauche, pour items de parcours patient |
| `dropdown` | Menu déroulant Alpine.js avec transitions fluides |
| `field` | Wrapper input texte avec label et icône optionnelle |
| `filter-pills` | Groupe de boutons filtres actifs avec état Alpine |
| `icon` | Icône SVG, 30+ icônes Heroicons disponibles |
| `patient-search` | Combo-box recherche patient par code/nom |
| `queue-list` | Conteneur liste patients pour salle d'attente |
| `select` | Wrapper select natif avec label |
| `slide-over` | Panneau drawer plein écran pour formulaires |
| `stat-card` | Tuile KPI avec label, valeur, icône et tendance optionnelles |
| `textarea` | Wrapper textarea avec label |
| `timeline` | Indicateur de progression par étapes (done/current/pending) |

## Layout

- **App Shell** (`layout/app-shell`) : sidebar fixe 64px (mobile: drawer w-72), header 64px, contenu max-w-7xl
- **Sidebar Nav** (`layout/sidebar-nav`) : items filtrés par rôle, état actif avec bordure brand-500
- **Background** : `bg-surface-sunken` (#f7f9fb) pour le body
- **Cards** : `rounded-lg border border-line bg-white shadow-card`

## Palette couleurs

### Primaire (Brand — Salesforce cloud blue)

| Token | Hex | Usage |
|-------|-----|-------|
| brand-50 | #eef7fe | Tints légers, badges |
| brand-100 | #dbeefd | Backgrounds légers |
| brand-200 | #b3ddfb | Bordures brand |
| brand-300 | #7cc3f7 | Hover states |
| brand-400 | #3aa1ef | Éléments interactifs |
| **brand-500** | **#0176d3** | **Actions principales, focus rings, états actifs** |
| brand-600 | #0161ab | Hover primary |
| brand-700 | #014486 | Texte brand sur fond clair |
| brand-800 | #02386b | Texte brand sombre |
| brand-900 | #032d60 | Texte brand très sombre |

### Texte (Ink)

| Token | Hex | Usage |
|-------|-----|-------|
| ink | #16325c | Texte principal |
| ink-soft | #3e4551 | Texte secondaire, contenu table |
| ink-muted | #706e6b | Placeholders, timestamps, labels |

### Surfaces

| Token | Hex | Usage |
|-------|-----|-------|
| surface | #ffffff | Backgrounds cartes |
| surface-muted | #f3f3f3 | Boutons muted, tints avatar |
| surface-sunken | #f7f9fb | Body, en-têtes table, hover rows |

### Sémantique

| Token | Hex | Usage |
|-------|-----|-------|
| success | #04844b | États positifs, payé |
| warning | #a35200 | Alertes |
| danger | #ba0517 | Erreurs, urgent, destructif |
| line | #dddbda | Bordures, diviseurs |

## Typographie

- **Family** : Inter / Figtree
- **Weights** : 400 (normal), 500 (medium), 600 (semibold), 700 (bold)
- **Titres** : `text-2xl font-semibold text-ink` (page), `text-base font-semibold` (carte), `text-sm font-semibold uppercase tracking-wide text-ink-muted` (section)
- **Body** : `text-sm text-ink`
- **Meta** : `text-xs text-ink-muted`
- **Mono** : `font-mono text-[11px] text-ink-muted` (IDs, timestamps)

## Espacements

- **Page** : `px-4 py-6` / `sm:px-6 sm:py-8`
- **Max-width** : `max-w-7xl mx-auto`
- **Gaps sections** : `gap-4` (cartes), `gap-6` (sections majeures)
- **Padding carte** : `p-6` (défaut), `p-5` (lignes liste)
- **Cellules table** : `px-5 py-3.5` (données), `px-5 py-3` (en-têtes)
- **Form gap** : `flex flex-col gap-1.5` (label→input)
- **Nav items** : `px-3 py-2.5`

## États composants

- **Focus** : `focus-visible:ring-2 focus-visible:ring-brand-200`
- **Disabled** : `disabled:opacity-50 disabled:pointer-events-none`
- **Hover** : `hover:bg-brand-600` (primary), `hover:bg-surface-muted` (nav inactive)
- **Transitions** : `transition-colors duration-100`
- **Border radius** : `rounded-md` (boutons, inputs), `rounded-lg` (cartes)
- **Shadows** : `shadow-card` (0 1px 2px rgba(22,50,92,0.06)), `shadow-card-hover` (0 4px 14px rgba(22,50,92,0.08))

## Icônes

- Style : Heroicons 24x24 outline
- Tailles : `h-5 w-5` (défaut), `h-4 w-4` (small)
- Props : `solid` pour variante remplie
- Disponibles : grid, menu, user-plus, credit-card, users, stethoscope, beaker, capsule, cog, bell, search, chevron-left/right/down, plus, check-circle, clock, alert-triangle, x-circle, shield-check, building, dots-vertical, download, filter, calendar, chart-bar, document, logout, mail, lock, arrow-right, identification, heart, phone, bed, archive-box, lock-closed, lock-open, banknotes, eye, clipboard-document, navigation

## Conventions vues

- **Tables** : en-tête `bg-surface-sunken text-xs uppercase tracking-wide`, hover `hover:bg-surface-sunken`
- **Badges inline** : `inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm`
- **KPIs** : cartes `stat-card` avec icône, label, valeur, tendance
- **Filtres** : pills avec état actif brand, inactif `text-ink-soft`
- **Formulaires** : wrapper `field` avec label au-dessus, erreurs en rouge danger
- **Slide-over** : drawer plein écran pour formulaires complexes (nouvelle ordonnance, etc.)
- **Mobile-first** : responsive avec breakpoints sm/md/lg, sidebar en drawer sur mobile

## Règles UX non négociables

- Accessibilité WCAG AA (contraste 4.5:1 texte, 3:1 interface)
- Cible tactile 44×44px minimum
- Feedback immédiat (< 100ms) ou état de chargement
- Pas d'emoji comme icône fonctionnelle
- Pas de dark pattern
- Hiérarchie visuelle : une seule action principale par écran
- États d'erreur, vide et chargement systématiques
- Contenu réel, jamais de placeholder générique

## Usage

Lorsque l'agent `ux-ui-designer` est invoqué :
1. Il consulte ce skill pour les patterns, couleurs et composants disponibles
2. Il s'aligne sur les conventions existantes — pas de redesign complet
3. Il propose des wireframes textuels structurés et des spécifications précises
4. Il documente dans `docs/design/` et passe la main à `fullstack-devops` avec un brief
