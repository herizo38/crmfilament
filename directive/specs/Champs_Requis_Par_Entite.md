# Champs Requis par Entité — CRM EspoCRM AOPIA / LIKE Formation

*Extrait du Cahier des Charges et des Comptes-Rendus de Réunion*

---

## 1. Partenaire (Account)

| Champ | Type | Remarque |
|---|---|---|
| Nom du partenaire | Texte | Nomenclature imposée : `[Type] [Nom entreprise] [Ville]` |
| Type | Liste | CSE / Syndicat / Association-Club / Entreprise / Partenariat annulé |
| État / Statut | Liste | À prospecter / En cours de prospection / Signé accord cadre / Convention engagement / Refus |
| Adresse complète | Adresse | Rue, CP, Ville |
| Département | Texte | Pour filtrer par zone commerciale |
| Commercial / Mandataire | Lien Utilisateur | Commercial assigné |

---

## 2. Prospect (Lead / LeadPhoning)

| Champ | Type | Remarque |
|---|---|---|
| Raison sociale (name) | Texte | Nom du prospect |
| Statut | Liste | AC / En cours / STD-NR / STD-Joint / CSE-NR / RP / RPC / KO / QF |
| Conseiller (assignedUser) | Lien Utilisateur | Téléprospecteur affecté |

### Champs obligatoires pour transition AC → En cours

| Champ | Type |
|---|---|
| Date de l'appel | Date |
| Heure de l'appel | Heure |
| Résultat de l'appel | Liste |

### Champs obligatoires pour passage en QF (7 éléments bloquants)

| # | Élément | Source |
|---|---|---|
| 1 | RDV créé dans le CRM (date, heure, lieu sur site) | Téléprospecteur |
| 2 | Email de confirmation envoyé au CSE | Automatique (Template 1) |
| 3 | Tous les champs obligatoires de la fiche renseignés | Téléprospecteur |
| 4 | Fichier récap PDF généré | Automatique |
| 5 | Enregistrement audio de la conversation | Téléprospecteur |
| 6 | Email invitation agenda envoyé au commercial | Automatique (Template 2) |
| 7 | Validation Team Leader effectuée | Team Leader |

### Champs obligatoires pour statut KO

| Champ | Type |
|---|---|
| Motif KO | Liste (obligatoire) |

---

## 3. Opportunité

| Champ | Type | Remarque |
|---|---|---|
| Nom de l'entité ciblée | Texte | Raison sociale |
| Source de détection | Liste | Réseau commercial / Client existant / Parrainage / Phoning entrant / Salon / LinkedIn / Fichier externe / Autre |
| Statut | Liste | Nouveau / En cours d'évaluation / Qualifiée / Converti / Perdue |
| Date de détection | Date | Auto-remplie à la création |

### Champ obligatoire conditionnel

| Condition | Champ requis | Type |
|---|---|---|
| Statut = Perdue | Raison de perte | Liste |

---

## 4. Client / Bénéficiaire (Contact importé Dolibarr)

Les champs ci-dessous sont importés depuis Dolibarr et considérés comme requis pour l'identification :

| Champ | Type | Remarque |
|---|---|---|
| Nom (lastName) | Texte | Séparation depuis le champ "Tiers" Dolibarr |
| Prénom (firstName) | Texte | Séparation depuis le champ "Tiers" Dolibarr |

### Clé de déduplication

| Priorité | Champs |
|---|---|
| 1 (principale) | Réf. client (ID technique Dolibarr) |
| 2 (fallback) | Nom + Prénom + Date de naissance |

---

## 5. RDV / Appel (Call / Meeting)

### Champs obligatoires pour un RDV commercial

| Champ | Type | Remarque |
|---|---|---|
| Date et heure | DateTime | |
| Lieu (sur site uniquement) | Adresse structurée | Rue / Code postal / Ville |
| Nom de l'interlocuteur | Texte | Contact CSE |
| Commercial assigné | Lien Utilisateur | |

### Champs obligatoires pour un appel (historique)

| Champ | Type |
|---|---|
| Date et heure | DateTime |
| Résultat | Liste (STD-Joint / STD-NR / CSE-NR / KO / RPC / RP / QF) |
| Commercial / Téléprospecteur concerné | Lien Utilisateur |

---

## 6. Contact (lié au Partenaire)

Aucun champ strictement obligatoire selon le CDC — tous les champs du bloc Contact sont optionnels. Les contacts sont des interlocuteurs libres associés au partenaire.

---

## 7. Règles de transition entre statuts (récapitulatif)

| Statut source | Statut cible | Champs obligatoires pour la transition |
|---|---|---|
| AC | En cours | Date, heure, résultat (au moins 1 appel) |
| En cours | STD-NR | 3 entrées historique (3 tentatives horaires distincts) |
| En cours | CSE-NR | Nom standard si obtenu |
| En cours / RP / RPC | Prise de RDV | Date RDV, lieu, nom interlocuteur |
| Prise de RDV | QF | PDF + audio + emails + validation TL (7 éléments) |
| Tout statut | KO | Motif KO (liste obligatoire) |
| Refus | À prospecter | Note de reprise de contact |

---

*Document généré depuis le CDC v1.0 et les CR de réunion — Mai 2026*
