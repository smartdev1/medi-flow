Voici un prompt optimisé pour **Stitch** (ou tout autre agent IA spécialisé dans la conception d'interfaces UI/UX). Il est conçu pour générer une application moderne, professionnelle et adaptée à un environnement clinique en s'inspirant du système décrit dans votre document. Le système est centré sur le suivi du patient, depuis son arrivée jusqu'à sa sortie, avec un identifiant unique, un statut évolutif et des interfaces propres à chaque service. 

---

# PROMPT STITCH

Tu es un UX/UI Designer Senior spécialisé dans les applications SaaS destinées au secteur de la santé.

Ta mission est de concevoir **l'intégralité des interfaces utilisateur (UI)** d'une application web responsive permettant de gérer la traçabilité complète des patients dans une clinique.

L'application doit être moderne, intuitive, professionnelle, très fluide et optimisée pour des secrétaires médicales, médecins, infirmiers, laborantins, pharmaciens, caissiers et administrateurs.

Le design doit s'inspirer des meilleurs logiciels hospitaliers (Doctolib Pro, Epic, Cerner, MediBoard, etc.) tout en restant simple.

Le style graphique doit être :

* moderne
* minimaliste
* lumineux
* professionnel
* très lisible
* orienté productivité
* adapté à une utilisation intensive

Palette :

* Bleu médical (#2563EB)
* Vert santé (#10B981)
* Blanc
* Gris très clair
* Rouge uniquement pour les urgences
* Orange pour les alertes

Utiliser :

* grandes cartes
* tableaux modernes
* timeline
* badges colorés
* icônes HeroIcons/Lucide
* coins arrondis
* ombres légères
* animations discrètes

---

# OBJECTIF DE L'APPLICATION

L'application doit permettre de suivre un patient depuis :

Accueil

↓

Caisse

↓

Consultation

↓

Laboratoire (si nécessaire)

↓

Retour médecin

↓

Pharmacie

↓

Sortie

Chaque patient possède :

* un ID Patient permanent
* un ID Visite unique à chaque venue

Le système affiche en permanence :

* où se trouve le patient
* son statut actuel
* le temps passé dans chaque service
* l'historique complet de son parcours

Le changement de statut doit déplacer automatiquement le patient dans la file d'attente du service suivant. 

---

# RÔLES UTILISATEURS

Créer des interfaces spécifiques pour :

* Administrateur
* Accueil
* Caisse
* Médecin
* Pédiatre
* Laboratoire
* Pharmacie
* Direction

Chaque rôle possède uniquement les fonctionnalités qui le concernent.

---

# STRUCTURE GÉNÉRALE

Créer :

## 1. Page de connexion

Design premium

Logo clinique

Email

Mot de passe

Connexion

Mot de passe oublié

Illustration médicale

---

## 2. Dashboard Administrateur

Cartes statistiques :

Patients présents

Patients sortis

Patients en attente

Patients hospitalisés

Temps moyen d'attente

Recettes du jour

Graphiques :

Patients par heure

Patients par service

Recettes

Répartition des consultations

Alertes

Activité récente

Notifications

---

## 3. Dashboard Accueil

Grand bouton :

"Nouveau patient"

Recherche patient

Création dossier

Liste des patients arrivés aujourd'hui

Patient déjà enregistré

Patient CMU

Patient Assurance

Patient Privé

Statut du patient

Heure d'arrivée

Orientation

Médecin

Pédiatrie

Urgence

---

## 4. Interface Caisse

Liste des patients en attente de paiement

Paiement consultation

Paiement laboratoire

Paiement pharmacie

Historique des paiements

Reçu PDF

Méthodes de paiement :

Espèces

Carte

Mobile Money

Assurance

CMU

Statut :

Payé

Partiellement payé

En attente

---

## 5. Salle d'attente

Grand écran temps réel

Patients en attente

Numéro

Nom

Heure d'arrivée

Temps d'attente

Statut

Service suivant

Tri dynamique

---

## 6. Dashboard Médecin

Patients en attente

Fiche patient

Constantes

Historique

Diagnostic

Ordonnance

Examens demandés

Orientation

Laboratoire

Pharmacie

Hospitalisation

Sortie

Timeline du patient

---

## 7. Dashboard Pédiatrie

Même logique

Adaptée aux enfants

Poids

Taille

Vaccins

Croissance

---

## 8. Dashboard Laboratoire

Patients attendus

Examens

Prélèvement

Résultats

Résultat prêt

Envoyer au médecin

Historique examens

Statut

---

## 9. Dashboard Pharmacie

Patients en attente

Ordonnance

Liste médicaments

Disponibilité stock

Rupture

Substitution

Validation

Historique délivrance

---

## 10. Sortie Patient

Résumé complet

Paiements

Consultations

Examens

Ordonnances

Documents

Compte rendu

Clôture dossier

---

# MODULE PATIENT

Créer une fiche patient complète :

Photo

Nom

Prénom

Sexe

Date naissance

Téléphone

Email

Adresse

Personne à contacter

Assurance

CMU

Historique

Antécédents

Allergies

Consultations

Documents

Timeline complète

---

# TIMELINE

Créer une magnifique timeline verticale :

Accueil

↓

Paiement

↓

Consultation

↓

Laboratoire

↓

Retour consultation

↓

Pharmacie

↓

Sortie

Chaque étape possède :

heure

agent

durée

statut

icône

couleur

---

# FILE D'ATTENTE

Créer un écran moderne affichant :

Patients en attente

Priorité

Urgence

Temps

Statut

Recherche

Filtres

Tri

---

# NOTIFICATIONS

Prévoir :

Notifications temps réel

Badges

Alertes

Patients urgents

Retard

Paiement manquant

Résultat laboratoire disponible

---

# RECHERCHE GLOBALE

Créer une recherche intelligente permettant de retrouver :

Patient

Visite

Paiement

Consultation

Ordonnance

Examen

---

# HISTORIQUE

Créer un écran retraçant entièrement le parcours du patient avec :

chronologie

heures

services

personnel

statuts

documents

paiements

---

# ADMINISTRATION

Créer les interfaces :

Gestion utilisateurs

Gestion rôles

Permissions

Services

Tarifs

Assurances

CMU

Laboratoires

Médicaments

Stocks

Journal d'activité

Paramètres

---

# RAPPORTS

Créer des interfaces analytiques :

Patients par jour

Recettes

Temps moyen

Patients par médecin

Patients par service

Laboratoire

Pharmacie

Hospitalisations

Graphiques interactifs

Export PDF

Export Excel

---

# DESIGN SYSTEM

Créer un Design System complet comprenant :

Typography

Buttons

Cards

Badges

Inputs

Dropdown

Table

Modal

Drawer

Timeline

DataGrid

Notification

Toast

Progress

Skeleton

Charts

Stat Cards

Tabs

Stepper

Calendar

Search Bar

Sidebar

Navbar

Breadcrumb

Pagination

Empty State

Loading State

Error State

Success State

---

# RESPONSIVE

Créer les interfaces pour :

Desktop

Tablette

Mobile

Les écrans doivent être parfaitement adaptatifs.

---

# LIVRABLES

Produire :

* Toutes les pages de l'application.
* Tous les composants réutilisables.
* Tous les états (vide, chargement, erreur, succès).
* Les interactions UX.
* Les transitions.
* Les maquettes haute fidélité.
* Un design cohérent et homogène sur toute l'application.
* Une architecture de navigation claire avec sidebar, header, breadcrumb et tableaux modernes.

Le résultat doit donner l'impression d'un logiciel hospitalier SaaS premium prêt pour une utilisation en production, avec une excellente ergonomie, une navigation fluide et une expérience utilisateur optimisée pour les professionnels de santé.
