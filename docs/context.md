# Contexte projet

## Client
Clinique Saint-Matthieu (CI)

## Objectif
Gérer les rondes de caissiers avec ouverture/fermeture de session, suivi
des soldes d'ouverture et de fermeture, et détection des écarts de caisse.

## Public cible
Caissiers (plusieurs, travail en rondes), Docteur Général (supervision),
Administrateur (lecture globale).

## Contraintes
- Local uniquement, aucun déploiement
- Laravel 11, stack Blade + Tailwind CSS
- Pas d'intégration comptable externe

## Stack retenue
- Backend : Laravel 11 (PHP 8.2+)
- Frontend : Blade + Tailwind CSS
- Base de données : MySQL/MariaDB
- Tests : PHPUnit (fournis par le projet)
- ADR de référence : `docs/adr/0001-cashier-session-model.md`

## Rôles métier
- `caissier` : encaisse les paiements, ouvre/ferme sa session
- `docteur_general` : supervise toutes les caisses en lecture seule
- `admin` : accès total

## Livraison attendue
Module caisse avec sessions de caissiers, balances d'ouverture/fermeture,
détection d'écarts, et historique des sessions.
