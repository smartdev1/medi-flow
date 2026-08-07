# 📖 Guide Utilisateur — MediFlow

> **Système de Gestion Clinique Moderne**
> *Application web pour la gestion intégrée d'un établissement de santé*

---

## 📑 Sommaire

1. [Présentation générale](#1-présentation-générale)
2. [Premiers pas](#2-premiers-pas)
3. [Les rôles et leurs accès](#3-les-rôles-et-leurs-accès)
4. [Utilisation par rôle](#4-utilisation-par-rôle)
   - [Agent d'accueil](#agent-daccueil)
   - [Caissier](#caissier)
   - [Médecin / Pédiatre](#médecin--pédiatre)
   - [Technicien de laboratoire](#technicien-de-laboratoire)
   - [Pharmacien](#pharmacien)
   - [Agent de logistique](#agent-de-logistique)
   - [Administrateur](#administrateur)
5. [Parcours complet d'un patient](#5-parcours-complet-dun-patient)
6. [Questions fréquentes (FAQ)](#6-questions-fréquentes-faq)
7. [Besoin d'aide ?](#7-besoin-daide)

---

## 1. Présentation générale

### 🎯 À quoi sert MediFlow ?

MediFlow est une application web qui centralise la gestion quotidienne d'une clinique ou d'un centre de santé. Elle remplace les cahiers, fichiers Excel et post-it par un outil unique où chaque service (accueil, consultation, laboratoire, pharmacie, caisse, hospitalisation) dispose de son propre espace de travail, tout en partageant les mêmes informations patient.

### ✨ Ce que vous pouvez faire avec MediFlow

- **Accueillir** les patients à leur arrivée et créer leur dossier
- **Orienter** les patients vers le bon service (médecin, pédiatrie, urgences, laboratoire)
- **Consulter** et rédiger les diagnostics et ordonnances
- **Suivre** les examens de laboratoire (prélèvements, résultats)
- **Délivrer** les médicaments prescrits à la pharmacie
- **Encaisser** les paiements et générer les reçus
- **Gérer** les hospitalisations et les chambres
- **Administrer** le personnel, les rôles et les paramètres

### 🛡️ Confidentialité et sécurité

MediFlow applique une règle simple : **chacun ne voit que ce dont il a besoin pour travailler**.

- Les données médicales (diagnostics, résultats) restent entre le patient et le personnel soignant.
- Les données financières restent confidentielles pour la caisse.
- Toutes les actions importantes sont **traçables** (qui a fait quoi, et quand).

> 💡 Pour en savoir plus sur les rôles, consultez la section [Les rôles et leurs accès](#3-les-rôles-et-leurs-accès).

---

## 2. Premiers pas

### 🌐 Se connecter à l'application

1. Ouvrez votre navigateur (Chrome, Firefox, Edge…).
2. Dans la barre d'adresse, saisissez l'URL fournie par votre administrateur (par défaut : `http://localhost`).
3. Sur la page de connexion, saisissez votre **adresse e-mail** et votre **mot de passe**.
4. Cliquez sur le bouton **Log in**.

> 🔒 Votre identifiant et votre mot de passe vous ont été remis par l'administrateur de la clinique. Ne les communiquez à personne.

### 🏠 Votre tableau de bord

Une fois connecté, vous arrivez automatiquement sur la page d'accueil correspondant à **votre rôle**. Par exemple :

| Votre rôle | Page d'accueil |
|---|---|
| Agent d'accueil / Administrateur | **Accueil & Réception** |
| Caissier | **Caisse** |
| Médecin / Pédiatre | **Espace Médecin** |
| Technicien de laboratoire | **Laboratoire** |
| Pharmacien | **Pharmacie** |
| Agent de logistique | **Logistique** |

La navigation est identique partout : un menu en haut, votre nom en haut à droite, et un bouton **Log Out** pour vous déconnecter en fin de poste.

### 👤 Modifier votre profil

Cliquez sur votre nom (en haut à droite), puis sur **Profile**. Vous pouvez y mettre à jour vos informations personnelles et votre mot de passe.

---

## 3. Les rôles et leurs accès

MediFlow distingue **huit rôles** principaux. Chacun dispose d'un espace dédié, adapté à son métier.

| Rôle | Accès principal | Ce qu'il peut faire |
|---|---|---|
| 🟢 **Agent d'accueil** | Accueil & Réception | Enregistrer les patients, créer les dossiers, orienter vers les services |
| 💰 **Caissier** | Caisse | Enregistrer les paiements, générer les reçus, suivre les impayés |
| 🩺 **Médecin** | Espace Médecin | Consulter, diagnostiquer, prescrire, orienter |
| 👶 **Pédiatre** | Espace Médecin (Pédiatrie) | Consulter, diagnostiquer, prescrire, orienter (patients enfants uniquement) |
| 🔬 **Technicien de laboratoire** | Laboratoire | Réaliser les examens et saisir les résultats |
| 💊 **Pharmacien** | Pharmacie | Délivrer les médicaments prescrits |
| 📦 **Agent de logistique** | Logistique | Gérer les médicaments, les commandes et les stocks |
| ⚙️ **Administrateur** | Personnel & Paramètres | Gérer les comptes utilisateurs, les rôles, les gardes |

> 📌 Pour le détail complet des droits par rôle, consultez le document `gestion roles perimetre activite.md` disponible dans le projet.

---

## 4. Utilisation par rôle

### 🟢 Agent d'accueil

> *Votre mission : accueillir les patients, créer ou retrouver leur dossier, et les orienter vers le bon service.*

#### 📍 Page : Accueil & Réception

C'est votre tableau de bord. Vous y voyez :

- **4 indicateurs clés** en haut de la page :
  - **Arrivées aujourd'hui** : nombre de patients enregistrés depuis l'ouverture
  - **En attente** : patients qui n'ont pas encore été consultés
  - **Urgences** : patients marqués prioritaires (à traiter en premier)
  - **Taux d'occupation** : pourcentage des lits occupés dans la clinique
- **Un tableau** listant les patients du jour avec : nom, identifiant (CSM-XXXX), heure d'arrivée, service d'orientation, prise en charge (Privé, CMU, Assurance) et statut.
- **Des filtres** en haut du tableau : *Tout*, *Privé*, *CMU*, *Assurance*.

#### ➕ Enregistrer un nouveau patient

1. Cliquez sur le bouton **+ Nouveau patient** (en haut à droite).
2. Un formulaire latéral s'ouvre. Renseignez :
   - **Patient déjà existant ?** Tapez le code (CSM-XXXX) ou le nom du patient pour le retrouver. Si vous le sélectionnez, ses informations seront pré-remplies — vous n'aurez plus qu'à mettre à jour l'orientation.
   - **Prénom**, **Nom**, **Sexe**, **Date de naissance**, **Téléphone**, **Prise en charge** (Privé / CMU / Assurance), **Orientation** (Médecin généraliste / Pédiatrie / Urgence / Laboratoire), **Motif de la visite** (en langage général, jamais un diagnostic).
   - Cochez **Cas prioritaire / urgent** si le patient doit être vu en priorité.
3. Cliquez sur **Enregistrer le patient**.
4. Le patient apparaît immédiatement dans la file d'attente du service choisi.

#### 👀 Voir le détail d'un patient

Cliquez sur **Détails** dans la ligne du patient pour consulter sa fiche (historique des visites, identité, contact).

#### 🔀 Modifier les coordonnées

Si un patient a déménagé ou changé de téléphone, vous pouvez mettre à jour ses informations depuis sa fiche détaillée.

---

### 💰 Caissier

> *Votre mission : encaisser les paiements des patients et générer les reçus.*

#### 📍 Page : Caisse

Vous y voyez la liste des patients en attente de paiement, avec le détail des actes à facturer :
- Ticket de consultation
- Examens de laboratoire
- Médicaments
- Hospitalisation

Vous voyez également le **type de couverture** du patient (Direct, CMU, Mutuelle, Assurance) pour calculer ce qui reste à sa charge.

#### 💵 Enregistrer un paiement

1. Sélectionnez le patient concerné.
2. Vérifiez le détail des actes à facturer.
3. Saisissez le **montant** et le **mode de paiement** (espèces, mobile money, carte, etc.).
4. Cliquez sur **Enregistrer**.
5. Un **reçu** est généré automatiquement — vous pouvez l'imprimer ou l'envoyer.

#### ✅ Vérifier qu'une visite est soldée

Avant la sortie du patient, vérifiez dans le tableau que toutes les lignes sont au statut **Soldé**. Sinon, vous pouvez signaler un **impayé** ou une **anomalie de paiement** depuis la fiche du patient.

#### 🚪 Clôturer une visite

Une fois tous les paiements encaissés, vous (ou l'agent d'accueil) pouvez **clôturer la visite** depuis la page **Sortie**. Le patient peut alors quitter la clinique.

> 🔎 **À noter** : en tant que caissier, vous ne voyez pas le contenu médical (diagnostic, prescriptions). Vous voyez uniquement les libellés des actes (ex : « Consultation générale », « Analyse sanguine »).

---

### 🩺 Médecin / Pédiatre

> *Votre mission : examiner les patients qui vous sont affectés, établir un diagnostic, prescrire des examens et/ou des médicaments, et orienter le patient.*

#### 📍 Page : Espace Médecin

Votre file d'attente affiche les patients qui vous sont affectés. Pour chaque patient, vous retrouvez :
- Les informations d'identité et l'historique des visites précédentes
- Le motif de la visite (saisi par l'accueil, en langage général)
- Les résultats de laboratoire déjà disponibles
- Le statut de paiement (savoir si le ticket de consultation a été réglé)

> 👶 Si vous êtes **pédiatre**, vous ne voyez que les patients orientés vers la Pédiatrie.

#### 🩻 Réaliser une consultation

1. Sélectionnez le patient dans votre file d'attente.
2. Renseignez :
   - **Symptômes** observés
   - **Diagnostic** (texte libre ou choix guidé)
   - **Notes complémentaires** (observations, antécédents)
3. **Pour prescrire des examens** (prise de sang, imagerie…) :
   - Allez dans la section *Examens*, ajoutez les examens demandés.
   - Le patient sera automatiquement ajouté à la file du **laboratoire**.
4. **Pour prescrire des médicaments** :
   - Allez dans la section *Ordonnance*, sélectionnez les médicaments et les dosages.
   - L'ordonnance apparaît dans la **file de la pharmacie**.
5. **Pour orienter le patient** :
   - Choisissez l'orientation (Laboratoire, Pharmacie, Hospitalisation, Sortie directe).
   - L'agent d'accueil et les autres services seront notifiés.

#### 🔬 Consulter les résultats de laboratoire

Dès que le laboratoire a terminé un examen, le résultat s'affiche automatiquement dans la fiche de votre patient. Vous pouvez alors ajuster votre diagnostic ou votre traitement.

#### 🏥 Hospitaliser un patient

Si l'état du patient le nécessite :

1. Sélectionnez le patient.
2. Cliquez sur **Hospitaliser**.
3. Choisissez une **chambre disponible** dans la liste.
4. Validez.

La chambre passe automatiquement en statut **Occupée** et le taux d'occupation se met à jour.

#### ✅ Clôturer une consultation

Une fois la consultation terminée et l'orientation décidée, cliquez sur **Clôturer la consultation**. Vous ne pourrez alors plus modifier vos notes.

> ⚠️ Tant que la consultation n'est pas clôturée, vous pouvez encore modifier vos propres notes. Une fois clôturée, seul un administrateur peut intervenir (à des fins d'audit).

---

### 🔬 Technicien de laboratoire

> *Votre mission : réaliser les examens prescrits par les médecins et en saisir les résultats.*

#### 📍 Page : Laboratoire

Votre tableau de bord affiche la liste des **examens en attente**, avec :
- Le nom du patient
- Le type d'examen demandé (ex : Numération formule sanguine, Glycémie…)
- L'âge et le sexe (utiles pour interpréter certains résultats)

> 🔎 Vous ne voyez **pas** le diagnostic du médecin ni ses autres prescriptions. C'est normal : vous n'avez besoin que de l'examen à réaliser.

#### 🧪 Traiter un examen

1. Sélectionnez l'examen dans votre file d'attente.
2. Cliquez sur **Démarrer le prélèvement** pour enregistrer l'heure de début.
3. Réalisez l'examen.
4. Saisissez les **résultats** dans les champs prévus (valeur, unité, commentaire si besoin).
5. Cliquez sur **Marquer comme terminé**.

Les résultats deviennent immédiatement visibles pour le **médecin prescripteur**, qui peut ajuster sa prise en charge.

#### ⚠️ Signaler un problème

Si l'examen ne peut pas être réalisé (matériel défaillant, patient absent…), vous pouvez **signaler l'incident** depuis la fiche de l'examen. Le médecin en sera informé.

---

### 💊 Pharmacien

> *Votre mission : délivrer les médicaments prescrits par les médecins.*

#### 📍 Page : Pharmacie

Vous y voyez la liste des **prescriptions en attente de délivrance**, avec :
- Le nom du patient
- Les médicaments prescrits (nom, dosage, quantité)
- Le statut de paiement (payé ou non)

> 🔎 Vous voyez les ordonnances de médicaments, mais **pas** le diagnostic, les notes de consultation ni les résultats de laboratoire.

#### 📦 Délivrer une ordonnance

1. Sélectionnez la prescription.
2. Vérifiez la **conformité** : dosage, quantité, absence d'interaction évidente.
3. Vérifiez que le **paiement** a bien été enregistré à la caisse (statut « Payé »).
4. Confirmez la **délivrance** : saisissez la quantité remise et la date.

Le statut de la prescription passe à **Délivré**, et le patient peut repartir avec ses médicaments.

#### 🚨 Signaler un problème

Si la prescription semble incorrecte (posologie anormale, interaction possible) ou si un médicament est en rupture de stock :
- Cliquez sur **Signaler un problème au médecin**.
- Le médecin sera notifié et pourra ajuster l'ordonnance.

> ⚠️ Vous **ne pouvez pas modifier** une prescription vous-même. Vous pouvez uniquement la signaler.

---

### 📦 Agent de logistique

> *Votre mission : approvisionner et gérer le stock de médicaments de la pharmacie.*

#### 📍 Page : Logistique

Vous y gérez :
- Le **catalogue des médicaments** (nom, dosage, forme, prix)
- Les **stocks** (entrées, sorties, ajustements)
- Les **commandes** aux fournisseurs (passation, réception)

#### ➕ Ajouter un nouveau médicament au catalogue

1. Cliquez sur **Nouveau médicament**.
2. Renseignez le nom, le dosage, la forme (comprimé, sirop, injectable…), le prix unitaire.
3. Validez. Le médicament devient disponible pour les ordonnances.

#### 📊 Ajuster un stock

Si vous recevez un lot de médicaments, ou si vous devez corriger un écart d'inventaire :
1. Recherchez le médicament.
2. Cliquez sur **Ajuster le stock**.
3. Saisissez la quantité (positive pour une entrée, négative pour une sortie ou correction).
4. Ajoutez un **commentaire** obligatoire (ex : *Réception lot XYZ – Facture 123*).
5. Validez.

#### 📋 Passer une commande fournisseur

1. Cliquez sur **Nouvelle commande**.
2. Ajoutez les lignes (médicament, quantité commandée, prix unitaire).
3. Validez. La commande apparaît avec le statut **En attente**.

#### 🚚 Réceptionner une commande

1. Ouvrez la commande concernée.
2. Vérifiez les quantités livrées (elles peuvent différer de la commande).
3. Saisissez les quantités réellement reçues.
4. Cliquez sur **Confirmer la réception**. Les stocks sont mis à jour automatiquement.
5. Vous pouvez **imprimer le bon de réception**.

---

### ⚙️ Administrateur

> *Votre mission : gérer les comptes utilisateurs, les rôles et les paramètres généraux.*

#### 📍 Page : Personnel

Vous y voyez la liste de tous les membres du personnel, leur rôle et leur statut (actif / désactivé).

#### ➕ Créer un compte

1. Cliquez sur **Nouveau membre**.
2. Renseignez : nom, e-mail, rôle principal.
3. Un **mot de passe temporaire** est généré automatiquement — transmettez-le au nouvel utilisateur (il devra le changer à sa première connexion).

#### 🔄 Modifier un rôle

1. Sélectionnez le membre concerné.
2. Cliquez sur **Modifier le rôle**.
3. Choisissez le nouveau rôle et validez.

> ⚠️ Modifier un rôle change immédiatement les accès de la personne. À utiliser avec précaution.

#### 🔌 Activer / désactiver un compte

Si un membre quitte la clinique ou est en congé prolongé :
1. Sélectionnez le membre.
2. Cliquez sur **Désactiver le compte**.
3. Le compte ne peut plus se connecter, mais l'historique de ses actions reste conservé.

#### 🔑 Réinitialiser un mot de passe

Si un utilisateur a perdu son mot de passe :
1. Sélectionnez le membre.
2. Cliquez sur **Réinitialiser le mot de passe**.
3. Un nouveau mot de passe temporaire est généré.

#### 🌙 Gérer les gardes

Depuis la section **Gardes** (en bas de la page Personnel) :
1. Cliquez sur **Nouvelle garde**.
2. Sélectionnez la date et le médecin de garde.
3. Validez. La garde s'affiche dans le tableau de bord des médecins.

> 🔎 L'**Administrateur** est un rôle à encadrer strictement. Il peut techniquement consulter toutes les données (à des fins de supervision), mais il **ne doit pas** modifier de diagnostic ou de prescription. Toutes ses actions sont tracées.

---

## 5. Parcours complet d'un patient

Pour vous aider à comprendre comment les rôles s'articulent entre eux, voici le **parcours typique** d'un patient dans la clinique :

```
1. 🟢 Accueil
   Le patient arrive → son dossier est créé ou retrouvé → il est orienté
   vers le service approprié.

2. 💰 Caisse (en parallèle)
   Il paie le ticket de consultation.

3. 🩺 Consultation
   Le médecin l'examine, pose un diagnostic, prescrit éventuellement des
   examens et/ou des médicaments.

4. 🔬 Laboratoire (si examens)
   Le technicien réalise les examens, saisit les résultats.

5. 💊 Pharmacie (si médicaments)
   Après paiement, le pharmacien délivre les médicaments.

6. 💰 Caisse (clôture)
   Le patient règle l'ensemble des actes.

7. 🚪 Sortie
   La visite est clôturée, le patient peut quitter la clinique.
   (S'il est hospitalisé, c'est 🏥 → puis 🚪 une fois guéri.)
```

---

## 6. Questions fréquentes (FAQ)

### 🤔 « Je ne trouve pas un patient dans ma file d'attente. Pourquoi ? »

- Vérifiez que vous êtes connecté avec **le bon rôle**.
- Si vous êtes médecin ou pédiatre, vous ne voyez que les patients qui vous sont **affectés**.
- Si vous êtes technicien de laboratoire, vous ne voyez que les patients avec un examen qui vous est attribué.

### 🤔 « J'ai fait une erreur de saisie. Comment corriger ? »

- **Tant que la consultation / délivrance / paiement n'est pas clôturé(e)**, vous pouvez modifier vos propres notes.
- Une fois clôturé(e), la modification passe par un **administrateur** (à des fins de traçabilité).

### 🤔 « Pourquoi je ne vois pas le diagnostic du patient ? »

C'est normal : MediFlow applique le **principe du moindre privilège**. Si votre rôle n'a pas besoin de connaître le diagnostic pour travailler, vous ne le voyez pas. C'est une protection pour le patient.

### 🤔 « Un patient a-t-il déjà payé ? »

Sur la fiche patient, une icône indique le statut « **Payé** » ou « **En attente de paiement** ». Vous pouvez aussi filtrer votre file par statut.

### 🤔 « Je dois m'absenter. Que faire ? »

- Pour une **courte absence** : laissez votre compte ouvert, un collègue avec le même rôle peut prendre le relais.
- Pour un **congé prolongé** : demandez à l'administrateur de vous désactiver (ou de vous remplacer par un autre utilisateur actif).

### 🤔 « Comment changer mon mot de passe ? »

Cliquez sur votre nom (en haut à droite) → **Profile** → champ *Mot de passe*. Saisissez votre mot de passe actuel, puis le nouveau (deux fois).

### 🤔 « Je ne peux pas me connecter. Qui contacter ? »

Contactez l'**administrateur** de la clinique. Il peut :
- Vérifier que votre compte est bien actif
- Vous réinitialiser votre mot de passe
- Vérifier votre rôle

---

## 7. Besoin d'aide ?

### 📞 Support interne

En cas de blocage, contactez d'abord votre **administrateur système** ou le **responsable de la clinique**. Il connaît les utilisateurs, les rôles et les procédures internes.

### 🐛 Signaler un bug

Si vous rencontrez un comportement inattendu (erreur, bouton qui ne répond pas, donnée manquante…) :

1. Notez **ce que vous faisiez** au moment du problème.
2. Notez le **message d'erreur** si possible (capture d'écran bienvenue).
3. Transmettez ces informations à l'administrateur avec **la date et l'heure**.

### 📚 Ressources

- **Ce guide** : `GUIDE_UTILISATEUR.md` (à la racine du projet)
- **Détail des rôles** : `gestion roles perimetre activite.md`
- **Politique de conformité** : `docs/compliance/politique-conformite.md`

---

## ✅ Bonnes pratiques

Pour une utilisation optimale de MediFlow au quotidien :

1. **Déconnectez-vous** toujours en fin de poste (surtout sur un poste partagé).
2. **Ne communiquez jamais** votre mot de passe — même à un collègue.
3. **Vérifiez l'identité du patient** avant chaque action sensible (prescrire, payer, délivrer).
4. **Évitez de laisser une consultation / délivrance « ouverte »** : clôturez dès que possible.
5. **Signalez tout problème** (matériel, données suspectes, patient mécontent) à votre responsable.
6. **Sauvegardez vos actions importantes** : cliquez sur *Enregistrer* régulièrement, ne comptez pas sur l'enregistrement automatique.

---

> *MediFlow — Version 1 — Guide utilisateur à destination du personnel clinique.*
> *Pour toute question sur ce guide, contactez l'administrateur du système.*
