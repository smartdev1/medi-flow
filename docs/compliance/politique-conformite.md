# Politique de conformité
## Système d'Information Hospitalier (SIH)

**Version :** 1.0.0  
**Statut :** Politique interne  
**Classification :** Confidentiel

---

# 1. Objet

Cette politique définit les règles de sécurité, de confidentialité et de gestion des données applicables au Système d'Information Hospitalier (SIH).

Elle a pour objectif de garantir :

- la confidentialité des informations médicales ;
- l'intégrité des données ;
- la disponibilité des services ;
- la traçabilité des opérations ;
- la conformité aux réglementations relatives aux données personnelles.

---

# 2. Champ d'application

Cette politique s'applique à :

- l'ensemble des employés de la clinique ;
- les médecins ;
- les infirmiers ;
- les secrétaires médicales ;
- les laboratoires ;
- les pharmaciens ;
- les administrateurs informatiques ;
- les prestataires techniques autorisés.

---

# 3. Classification des données

Les données manipulées sont classées selon les niveaux suivants.

## Données publiques

Informations destinées au public.

Exemples :

- horaires
- contacts
- informations administratives

---

## Données internes

Informations réservées au personnel.

Exemples :

- planning
- gestion interne

---

## Données confidentielles

Informations médicales ou personnelles.

Exemples :

- identité du patient
- dossier médical
- résultats d'analyse
- prescriptions
- diagnostics
- ordonnances
- antécédents
- allergies
- imagerie médicale

Ces données bénéficient du niveau maximal de protection.

---

# 4. Principe du moindre privilège

Chaque utilisateur possède uniquement les droits nécessaires à l'exercice de sa fonction.

Aucun utilisateur ne peut accéder à des données sans autorisation.

---

# 5. Comptes utilisateurs

Chaque employé dispose d'un compte personnel.

Les comptes partagés sont interdits.

Chaque compte doit être :

- identifiable ;
- authentifié ;
- journalisé.

---

# 6. Authentification

Les utilisateurs doivent utiliser :

- un identifiant unique ;
- un mot de passe robuste.

Le mot de passe doit respecter :

- longueur minimale : 12 caractères ;
- lettres majuscules ;
- lettres minuscules ;
- chiffres ;
- caractères spéciaux.

L'authentification multifacteur (MFA) est fortement recommandée pour les administrateurs et les accès distants.

---

# 7. Gestion des rôles

Le système applique un contrôle d'accès basé sur les rôles.

Exemples :

## Administrateur

Peut :

- gérer les utilisateurs ;
- gérer les rôles ;
- superviser le système.

Ne peut pas modifier un dossier médical sans justification fonctionnelle.

---

## Médecin

Peut :

- consulter les dossiers de ses patients ;
- créer des consultations ;
- modifier les prescriptions.

---

## Infirmier

Peut :

- consulter les dossiers nécessaires aux soins ;
- enregistrer les constantes.

Ne peut pas modifier les diagnostics.

---

## Secrétaire

Peut :

- enregistrer les patients ;
- gérer les rendez-vous.

Ne peut jamais consulter le contenu médical détaillé.

---

## Pharmacien

Peut :

- consulter les prescriptions ;
- enregistrer les délivrances.

---

# 8. Confidentialité

Toute personne ayant accès au système est tenue au secret professionnel.

Les informations médicales ne doivent jamais être :

- imprimées inutilement ;
- envoyées par messagerie non sécurisée ;
- partagées avec une personne non autorisée.

---

# 9. Chiffrement

Les communications réseau doivent utiliser TLS/HTTPS.

Les données sensibles stockées doivent être chiffrées lorsque cela est techniquement possible, en particulier :

- diagnostics ;
- résultats d'examens ;
- informations personnelles sensibles ;
- pièces jointes médicales.

Les mots de passe sont stockés uniquement sous forme de hachage sécurisé.

---

# 10. Hébergement

Les données médicales sont hébergées exclusivement sur le serveur local sécurisé de la clinique.

Le serveur doit être :

- placé dans une salle sécurisée ;
- protégé contre les accès physiques non autorisés ;
- protégé par un pare-feu ;
- maintenu à jour ;
- surveillé.

---

# 11. Sauvegardes

Des sauvegardes automatiques sont réalisées quotidiennement.

Les sauvegardes doivent être :

- chiffrées ;
- vérifiées régulièrement ;
- restaurables ;
- conservées selon la politique interne.

Une copie hors site ou sur un support distinct est recommandée afin de prévenir les pertes liées aux sinistres.

---

# 12. Journalisation

Le système enregistre automatiquement :

- les connexions ;
- les déconnexions ;
- les consultations de dossiers ;
- les créations ;
- les modifications ;
- les suppressions ;
- les exports ;
- les impressions.

Chaque journal contient :

- l'utilisateur ;
- la date ;
- l'heure ;
- l'adresse IP ou le poste utilisé ;
- l'action réalisée.

Les journaux ne peuvent être modifiés par les utilisateurs.

---

# 13. Disponibilité

Le système doit garantir :

- la disponibilité des dossiers médicaux ;
- la continuité des soins ;
- la restauration rapide après incident.

---

# 14. Conservation des données

Les dossiers médicaux sont conservés conformément aux exigences légales applicables.

À l'expiration de la durée de conservation, les données sont archivées ou supprimées selon la réglementation.

---

# 15. Droits des patients

Sous réserve de la législation applicable, le patient peut notamment :

- demander l'accès à son dossier ;
- demander la rectification des informations inexactes ;
- obtenir des informations sur le traitement de ses données.

Toute demande est traitée selon la procédure interne de la clinique.

---

# 16. Incident de sécurité

Tout incident doit être signalé immédiatement.

Exemples :

- perte de données ;
- accès non autorisé ;
- tentative d'intrusion ;
- infection par un logiciel malveillant ;
- vol de matériel ;
- fuite d'informations.

Une analyse est réalisée afin de :

- identifier la cause ;
- limiter les impacts ;
- restaurer le système ;
- mettre en œuvre des mesures correctives.

---

# 17. Développement sécurisé

Toute évolution du logiciel doit respecter les principes suivants :

- validation des entrées ;
- protection contre les injections SQL ;
- protection contre les attaques XSS ;
- protection CSRF ;
- gestion sécurisée des sessions ;
- contrôle d'accès systématique ;
- chiffrement des données sensibles ;
- revue de code ;
- journalisation des erreurs sans divulgation d'informations sensibles.

---

# 18. Maintenance

Les mises à jour de sécurité doivent être appliquées régulièrement.

Les composants obsolètes doivent être remplacés.

---

# 19. Responsabilités

## Direction

- définit la politique de sécurité ;
- valide les procédures.

## Administrateur système

- gère les serveurs ;
- applique les correctifs ;
- effectue les sauvegardes ;
- supervise les journaux.

## Personnel médical

- protège les informations des patients ;
- utilise uniquement son compte personnel.

## Développeurs

- développent conformément aux bonnes pratiques de sécurité ;
- corrigent les vulnérabilités identifiées.

---

# 20. Audit

Des audits de sécurité peuvent être réalisés périodiquement afin de vérifier :

- le respect de cette politique ;
- la sécurité des infrastructures ;
- les droits des utilisateurs ;
- la qualité des sauvegardes ;
- les journaux d'audit.

---

# 21. Sanctions

Toute violation de cette politique peut entraîner :

- une suspension des accès ;
- des mesures disciplinaires ;
- des poursuites civiles ou pénales selon la gravité des faits et la législation applicable.

---

# 22. Révision

Cette politique est revue au minimum une fois par an ou à chaque évolution majeure du système ou des exigences réglementaires.

---

# 23. Conclusion

La protection des données médicales constitue une responsabilité collective.

Chaque utilisateur du système est tenu de respecter cette politique afin d'assurer la confidentialité des informations, la sécurité des patients et la continuité des soins.