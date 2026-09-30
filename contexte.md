Parfait, tu veux un **fichier de contexte Markdown** qui serve de **charte technique** pour tes agents IA développeurs, en intégrant non seulement les bonnes pratiques Laravel mais aussi les principes **SOLID, DRY, KISS** et les règles de sécurité de l’**OWASP Web Top 10**. Voici une version enrichie et structurée :

---

# 📘 Contexte de Développement – Application de Gestion de Clinique

## 🎯 Objectif
Guider les agents IA développeurs dans l’évolution et la correction de l’application de gestion de clinique (Laravel 12, préproduction).  
Le code doit rester stable, maintenable et sécurisé, en respectant les standards de développement senior.

---

## ⚙️ Stack Technique
- **Framework** : Laravel 12 (PHP 8.2+)
- **Base de données** : MySQL/PostgreSQL
- **Front-end** : Blade + TailwindCSS
- **Déploiement** : Environnement préproduction
- **CI/CD** : Git (local pour l’instant, pas de push GitHub)

---

## 🧭 Principes Directeurs
- **SOLID** : Respecter les 5 principes pour un code extensible et maintenable.
- **DRY** : Éviter la duplication de logique, factoriser le code.
- **KISS** : Garder le code simple et compréhensible.
- **OWASP Web Top 10** : Appliquer les recommandations de sécurité (injections, authentification, gestion des sessions, etc.).
- **Stabilité** : Ne jamais casser l’existant, privilégier la rétrocompatibilité.
- **Tests** : Couvrir chaque correction ou ajout par des tests unitaires/fonctionnels.
- **Documentation** : Tenir à jour changelog et README technique.

---

## 🛠️ Workflow de Développement
1. **Branche locale dédiée**  
   - `feature/xxx` ou `fix/xxx` (même sans push GitHub).
2. **Revue interne**  
   - Vérifier la cohérence et la lisibilité avant merge.
3. **Tests automatisés**  
   - Lancer la suite de tests avant tout déploiement.
4. **Déploiement préprod**  
   - Vérifier migrations et seeders.
5. **Validation métier**  
   - Confirmer avec les besoins de la clinique.

---

## 🧩 Bonnes Pratiques Laravel
- Respect du **pattern MVC** : logique métier dans les Services, pas dans les contrôleurs.
- **Form Request** pour la validation des données.
- **Policies & Gates** pour la gestion des rôles et permissions.
- Variables sensibles uniquement via **.env**.
- **Jobs & Queues** pour les tâches lourdes.
- **Observers** pour la logique liée aux événements des modèles.
- **Eloquent** optimisé (éviter N+1, utiliser `with()`).

---

## 🔒 Sécurité – OWASP Web Top 10
- **Injection** : Utiliser Eloquent/Query Builder, jamais de SQL brut non sécurisé.
- **Authentification** : Laravel Sanctum/Passport, mots de passe hashés (bcrypt/argon2).
- **Sessions** : Cookies sécurisés (`HttpOnly`, `SameSite`).
- **XSS** : Échapper les données avec Blade (`{{ }}`).
- **CSRF** : Middleware CSRF activé par défaut.
- **Contrôle d’accès** : Vérifier les permissions à chaque action.
- **Logging & Monitoring** : Journaliser les accès et erreurs critiques.

---

## 🚨 Gestion des Bugs
- Identifier et reproduire le bug.
- Corriger en respectant SOLID, DRY, KISS.
- Ajouter un test qui couvre le bug.
- Documenter la correction dans le changelog.

---

## 📑 Exemple de Directive pour un Agent IA
> « Corrige le bug de double insertion dans la table `patients`.  
> Vérifie les contraintes d’unicité, applique le principe DRY pour éviter duplication de logique, ajoute un test unitaire, et assure-toi que la correction respecte les règles OWASP (validation des inputs). »

---