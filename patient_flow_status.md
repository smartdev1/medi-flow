# État des fonctionnalités de l’application clinique

## 1. Fonctionnalités déjà implémentées

### Accueil et gestion des patients
- Enregistrement d’un nouveau patient
- Recherche/sélection d’un patient existant
- Création d’une visite (`Visit`) avec :
  - direction (`orientation`)
  - raison de la consultation
  - statut (`En attente`, `Prioritaire`)
  - prise en compte de l’urgence

### Tableau de bord et circuit patient
- Affichage des arrivées du jour
- Suivi des patients en file d’attente
- Visualisation du circuit patient par étapes
- Indicateurs : nombre de patients, urgence, occupancy

### Consultation médicale
- Consultation déclenchée automatiquement pour un patient actif
- Saisie des constantes vitales
- Enregistrement des notes de consultation
- Clôture de consultation

### Examens de laboratoire
- Création d’un examen (`Exam`)
- Suivi du statut de l’examen
- Avancement du statut par le laboratoire

### Pharmacie / ordonnances
- Création d’ordonnances (`Prescription`)
- Délivrance des ordonnances
- Gestion de la rupture de stock partielle
- Génération d’un paiement lors de la délivrance

### Paiement et caisse
- Enregistrement des paiements
- Association du paiement à un patient et une visite
- Marquage d’un paiement comme payé
- Génération de reçus et téléchargement PDF
- Clôture de la caisse journalière

### Hospitalisation
- Admission des patients en chambre
- Attribution d’un lit disponible
- Gestion du statut d’hospitalisation
- Sortie d’admission

### Sortie patient
- Clôture de la visite active (`discharged_at`)
- Retrait du patient du circuit actif

## 2. Ce qui reste à implémenter — fonctionnel

### 2.1 Dossier médical structuré
- Fiche patient complète (antécédents, allergies, vaccinations)
- Historique médical détaillé
- Formulaires de consultation structurés
- Notes médicales plus riches et standardisées

### 2.2 Gestion tarifaire et facturation
- Catalogue de prestations médicales
- Tarification par acte / service
- Facturation automatique selon actes réalisés
- Gestion des devis et factures clients

### 2.3 Assurance et tiers-payant
- Prise en charge assurance
- Calcul du reste à charge
- Documents de garantie / prise en charge
- Règles de remboursement

### 2.4 Workflow de laboratoire avancé
- Résultats d’examen saisis et partagés
- Rapports de laboratoire structurés
- Pièces jointes (imagerie, PDF, comptes rendus)
- Suivi des délais et priorités d’examen

### 2.5 Documents et pièces jointes
- Upload de documents médicaux
- Archivage des rapports et résultats
- Gestion d’historique de documents


## 3. Ce qui reste à implémenter — opérationnel

### 3.1 Gestion des utilisateurs et permissions
- Rôles plus granulaires
- Administration des droits
- Audit des actions utilisateur
- Traçabilité des modifications

### 3.2 Reporting et tableaux de bord métier
- Statistiques d’activité et fréquentation
- Rapport de revenus, marges, paiements
- Indicateurs d’occupation et performances
- Rapports qualité et conformité

### 3.3 Planning du personnel
- Gestion des horaires et vacations
- Gestion des gardes et remplacements
- Disponibilités des médecins et du personnel

### 3.4 Gestion logistique avancée
- Suivi des lots et dates de péremption
- Inventaire avec seuil de réapprovisionnement
- Commandes fournisseur et réception
- Gestion des retours et ruptures de stock

### 3.5 Sécurité et conformité
- Chiffrement des données sensibles
- Contrôle d’accès renforcé
- Sauvegarde et restauration
- Conformité RGPD et protection des données

## 4. Recommandation prioritaire

Pour rendre l’application exploitable comme solution clinique complète, il est recommandé d’ajouter en priorité :
1. dossier médical structuré
2. facturation et assurance
3. reporting et audit
4. workflow laboratoire complet

> Ce fichier décrit l’existant, puis la feuille de route opérationnelle et fonctionnelle nécessaire pour une application clinique digne de ce nom.
