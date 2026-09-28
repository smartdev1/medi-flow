# Conventions de code

## Langue
- Code, commentaires, commits, noms de fichiers, branches : anglais
- Échanges utilisateur, documentation métier : français
- Interface utilisateur : français

## Nommage
- Models : PascalCase singulier (`CashierSession`)
- Tables : snake_case pluriel (`cashier_sessions`)
- Colonnes : snake_case (`opened_at`, `opening_balance`)
- Controllers : StudlyCase + `Controller` suffix (`CashierSessionController`)
- Routes : snake_case, préfixées par module (`caisse.sessions.index`)
- Policies : `ModelNamePolicy` (`CashierSessionPolicy`)
- Vues : snake_case, dans dossiers de module (`caisse/sessions/index.blade.php`)
- Fichiers de tâche : `T-NNN-short-title.md`

## Structure Laravel
- Un controller par domaine métier, pas de "GodController"
- Policies systématiques pour l'autorisation
- Form Requests pour toute validation de formulaire
- Scopes Eloquent pour les filtres réutilisables
- Pas de logique métier dans les vues (utiliser des accesseurs ou des view models)

## Commit messages
Conventional Commits en anglais : `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
Terminer par : `Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`

## Git
- Branches : `feat/T-001-short-title`, `fix/T-014-short-title`
- Jamais de commit direct sur main
- Jamais de force push
- Limite WIP : 2 tâches `doing` maximum

## Définition of Done
1. Critères d'acceptation remplis
2. Code relu par tech-lead-po
3. Tests pertinents existent et passent
4. Documentation à jour
5. QA a validé
6. Utilisateur a validé
