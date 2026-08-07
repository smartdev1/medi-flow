# Gestion des rôles et périmètre d'activité

## Objectif de ce document

Ce document définit **qui a le droit de faire quoi** dans le système de suivi patient. L'idée centrale est simple : chaque membre du personnel n'accède qu'aux informations et aux actions nécessaires à sa fonction, ni plus ni moins. C'est ce qu'on appelle le **principe du moindre privilège** : on donne à chacun uniquement ce dont il a besoin pour faire son travail.

Cela répond à plusieurs besoins :
- **Protéger la confidentialité** des données médicales (un patient a le droit à ce que son diagnostic reste entre lui et le personnel soignant).
- **Éviter les erreurs** (empêcher qu'un agent non médical modifie un diagnostic, par exemple).
- **Sécuriser les paiements** (seule la caisse doit pouvoir enregistrer ou modifier une transaction financière).
- **Garder une traçabilité fiable** : savoir qui a fait quoi, et être certain que la personne avait bien le droit de le faire.

---

## Les rôles identifiés

Le système distingue les rôles suivants, correspondant aux services de la clinique, plus un rôle transverse d'administration :

1. Agent d'accueil
2. Caissier
3. Médecin
4. Pédiatre
5. Technicien de laboratoire
6. Pharmacien
7. Administrateur du système

Chaque personne du personnel se voit attribuer **un seul rôle principal**, qui détermine ce qu'elle peut voir et faire dans le système. Une même personne pourrait, en théorie, cumuler deux rôles si la clinique le juge nécessaire (par exemple un médecin qui assure aussi des fonctions d'administration), mais ce sera l'exception, pas la règle.

---

## 1. Agent d'accueil

### Rôle général
Premier point de contact du patient. Responsable de l'enregistrement et de l'orientation initiale.

### Ce qu'il peut voir
- La liste des patients en attente à l'accueil
- Les informations d'identité d'un patient (nom, contact, historique de visites — mais pas le contenu médical)
- Le statut général d'un patient (à quelle étape il se trouve), sans le détail médical

### Ce qu'il peut faire
- Créer un nouveau dossier patient
- Retrouver un dossier existant
- Enregistrer le motif de la visite (en langage général, pas un diagnostic)
- Orienter le patient vers le Médecin ou la Pédiatrie
- Modifier les coordonnées d'un patient (adresse, téléphone)

### Ce qu'il ne peut pas voir ou faire
- Le contenu des consultations, diagnostics ou prescriptions
- Les résultats de laboratoire
- Les informations financières détaillées (montants, méthode de paiement) — il peut seulement savoir si le patient doit passer par la caisse ou non
- Modifier une décision médicale ou une prescription

---

## 2. Caissier

### Rôle général
Gère l'ensemble des flux financiers liés aux visites des patients.

### Ce qu'il peut voir
- La liste des patients en attente de paiement
- Le détail des actes à facturer (ticket de consultation, examens, médicaments, hospitalisation)
- L'historique des paiements d'un patient pour la visite en cours
- Le type de couverture du patient (Direct, CMU, Mutuelle, Assurance)

### Ce qu'il peut faire
- Enregistrer un paiement (peu importe le mode : espèces, mobile money, etc.)
- Générer un reçu
- Vérifier si une visite est entièrement soldée avant la sortie du patient
- Signaler un impayé ou une anomalie de paiement

### Ce qu'il ne peut pas voir ou faire
- Le contenu médical (diagnostic, résultats, prescriptions détaillées) — il voit uniquement les libellés des actes à facturer (ex : "Consultation générale", "Analyse sanguine"), pas leur contenu clinique
- Modifier un dossier médical
- Créer ou modifier une prescription

---

## 3. Médecin (généraliste)

### Rôle général
Prend en charge les patients adultes orientés vers la consultation générale.

### Ce qu'il peut voir
- La liste des patients qui lui sont affectés et en attente de consultation
- L'historique médical complet du patient (visites précédentes, diagnostics antérieurs, résultats de laboratoire, prescriptions passées)
- Le statut de paiement du patient (pour savoir si le ticket de consultation a été réglé), sans le détail des montants

### Ce qu'il peut faire
- Consulter le patient et enregistrer les symptômes et le diagnostic
- Rédiger des prescriptions (médicaments et/ou examens)
- Décider de l'orientation du patient (laboratoire, pharmacie, hospitalisation, sortie directe)
- Consulter les résultats de laboratoire dès qu'ils sont disponibles
- Modifier ses propres notes de consultation tant que la visite n'est pas clôturée

### Ce qu'il ne peut pas voir ou faire
- Les informations financières détaillées (montants exacts, mode de paiement)
- Les dossiers des patients qui ne lui sont pas ou n'ont pas été affectés (sauf consultation ponctuelle justifiée, par exemple un remplacement)
- Modifier les paiements ou les délivrances de pharmacie

---

## 4. Pédiatre

### Rôle général
Identique au Médecin généraliste, mais dédié aux patients orientés vers la Pédiatrie (enfants).

### Ce qu'il peut voir et faire
Les mêmes droits que le Médecin généraliste, mais **uniquement sur les patients orientés vers le service Pédiatrie**. Un pédiatre ne voit pas les patients affectés à la consultation générale, et inversement.

### Particularité
Les dossiers d'enfants peuvent nécessiter des informations complémentaires (nom du parent ou tuteur, contact d'urgence). Le pédiatre doit pouvoir consulter et compléter ces informations spécifiques, en plus des droits standards de consultation.

---

## 5. Technicien de laboratoire

### Rôle général
Réalise les examens prescrits par les médecins et enregistre les résultats.

### Ce qu'il peut voir
- La liste des examens en attente, avec le type d'examen demandé
- L'identité du patient concerné (nom, âge, sexe — utile pour l'interprétation de certains résultats)
- Les prescriptions d'examens (uniquement la partie "examen", pas les prescriptions de médicaments)

### Ce qu'il peut faire
- Enregistrer l'heure de prélèvement
- Saisir les résultats d'un examen
- Marquer un examen comme terminé, ce qui le rend visible pour le médecin prescripteur

### Ce qu'il ne peut pas voir ou faire
- Le diagnostic du médecin ni les autres éléments de la consultation
- Les prescriptions de médicaments
- Les informations financières
- Les résultats d'examens d'un patient qui ne lui a pas été confié

---

## 6. Pharmacien

### Rôle général
Délivre les médicaments prescrits et gère le suivi des stocks liés aux délivrances.

### Ce qu'il peut voir
- La liste des prescriptions de médicaments en attente de délivrance
- L'identité du patient concerné
- Le statut de paiement du médicament (payé ou non), sans le détail financier complet

### Ce qu'il peut faire
- Enregistrer la délivrance d'un médicament (quantité, date, patient concerné)
- Signaler une rupture de stock
- Vérifier la conformité d'une ordonnance avant délivrance

### Ce qu'il ne peut pas voir ou faire
- Le diagnostic ou les notes de consultation
- Les résultats de laboratoire
- Les informations financières détaillées (montants exacts, mode de paiement)
- Modifier une prescription (il peut seulement signaler un problème au médecin, pas la changer lui-même)

---

## 7. Administrateur du système

### Rôle général
Rôle technique et organisationnel transverse, généralement confié à une personne de la direction ou à un responsable informatique désigné. Ce rôle n'intervient pas dans les soins, mais dans la **gestion globale du système**.

### Ce qu'il peut voir
- L'ensemble des données de la clinique, tous services confondus (à des fins de supervision, de statistiques, ou de résolution de problème)
- Les statistiques globales (nombre de patients, temps moyens de passage, charge par service)
- La liste du personnel et les rôles attribués

### Ce qu'il peut faire
- Créer, modifier ou désactiver un compte du personnel
- Attribuer ou modifier le rôle d'un membre du personnel
- Consulter les journaux d'activité (qui a fait quoi, et quand) à des fins de contrôle ou d'audit
- Réinitialiser un mot de passe oublié
- Configurer les paramètres généraux du système (listes de services, types d'actes, etc.)

### Ce qu'il ne peut pas faire
- Modifier directement un diagnostic médical ou une prescription (ce n'est pas son rôle, même s'il peut techniquement consulter l'information à des fins de supervision)
- Ce rôle doit rester rare et attribué avec précaution, car il a une visibilité totale sur les données sensibles de la clinique

---

## Tableau récapitulatif du périmètre par rôle

| Rôle | Voit les données médicales | Voit les données financières | Modifie des prescriptions | Modifie des paiements | Portée |
|---|---|---|---|---|---|
| Agent d'accueil | Non | Non (statut seulement) | Non | Non | Tous les patients (identité seulement) |
| Caissier | Non | Oui | Non | Oui | Tous les patients (finances seulement) |
| Médecin | Oui | Non (statut seulement) | Oui (les siennes) | Non | Patients qui lui sont affectés |
| Pédiatre | Oui | Non (statut seulement) | Oui (les siennes) | Non | Patients Pédiatrie qui lui sont affectés |
| Technicien labo | Partiel (examens seulement) | Non | Non | Non | Patients avec examen qui lui est confié |
| Pharmacien | Partiel (prescriptions médicaments seulement) | Non (statut seulement) | Non | Non | Patients avec délivrance en attente |
| Administrateur | Oui (supervision) | Oui (supervision) | Non | Non | Tous les patients et tout le personnel |

---

## Principes transversaux à respecter

- **Un rôle = un périmètre défini.** Personne ne doit avoir accès "par défaut" à plus que ce que son rôle exige.
- **La affectation prime sur la fonction.** Un médecin ne voit en priorité que les patients qui lui sont attribués, pas l'ensemble des patients de la clinique (sauf cas particulier, comme un remplacement, qui peut nécessiter une autorisation ponctuelle).
- **Les données financières et les données médicales sont cloisonnées.** Aucun rôle non médical ne doit avoir accès au contenu clinique (diagnostic, résultats, notes), et aucun rôle médical n'a besoin de voir le détail financier.
- **Toute action reste tracée.** Quel que soit le rôle, chaque action effectuée dans le système (créer, modifier, consulter une information sensible) doit être enregistrée avec l'identité de la personne et l'heure, pour permettre un contrôle a posteriori si nécessaire.
- **Le rôle Administrateur est une exception à encadrer strictement.** Sa visibilité totale sur le système est nécessaire à la supervision, mais doit être réservée à un nombre très limité de personnes, avec un suivi renforcé de ses actions.

---

## Ce que ce document ne couvre pas

Ce document définit le **périmètre fonctionnel** de chaque rôle (qui peut voir/faire quoi). Il ne couvre pas encore :

- La façon technique dont ces droits seraient mis en œuvre (système de connexion, structure des permissions dans la base de données)
- La gestion des cas particuliers (remplacement temporaire, urgence nécessitant un accès élargi, congé d'un membre du personnel)
- La procédure de création et de désactivation des comptes du personnel

Ces éléments pourront faire l'objet d'un travail complémentaire si nécessaire.
