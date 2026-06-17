# Cahier des Charges — CRM Commercial EspoCRM
**AOPIA / LIKE Formation — NS Conseil**
Version 1.0 — Mai 2026 | Statut : Projet à valider

| Champ | Information |
|---|---|
| Entreprise cliente | AOPIA / LIKE Formation — NS Conseil |
| Interlocuteur principal | Alexandre FLOREK — Co-Gérant |
| Responsable partenariats | Bruno BIARDOUX |
| Responsable ADV/RC | Franck PINO CORTES |
| Solution retenue | EspoCRM (self-hosted) |
| Prestataire ERP actuel | Emmanuel OYEZ — NORD ERP CRM (Dolibarr) |

---

## 1. Contexte et objectifs

AOPIA et LIKE Formation (marques de NS Conseil) sont des organismes de formation professionnelle. L'activité commerciale repose sur deux axes : la gestion de partenaires (CSE, syndicats, entreprises) et le suivi des clients bénéficiaires.

L'entreprise dispose actuellement de plusieurs outils distincts :
- Un CRM Dolibarr gérant la facturation, les paiements et le suivi pédagogique des formateurs
- Des fichiers Excel dispersés pour la gestion des partenaires, des permanences et du phoning
- Aucune solution unifiée pour le pilotage commercial

**Objectif :** déployer EspoCRM comme CRM commercial dédié, alimenté régulièrement par des exports Excel issus de Dolibarr, sans développement d'interface temps réel entre les deux systèmes.

### Périmètre inclus

- Gestion et prospection des partenaires (CSE, syndicats, entreprises)
- Gestion des clients bénéficiaires
- Suivi des actions commerciales et des RDV (phoning, permanences)
- Reporting et tableaux de bord commerciaux
- Mail hebdomadaire automatisé aux commerciaux
- Base de connaissances (procédures, scripts, FAQ)

### Périmètre exclu

- Facturation, paiements, impayés → restent dans Dolibarr
- Suivi pédagogique des formateurs → reste dans Dolibarr
- Génération de devis

---

## 2. Arborescence du CRM

```
EspoCRM Commercial
├── Partenaires
│   ├── Fiche partenaire
│   │   ├── Informations générales
│   │   └── Documents joints (convention, FDR…)
│   ├── Contacts liés
│   └── Suivi des actions
│       ├── Appels
│       └── Permanences
│
├── Prospects
│   ├── Pipeline (statuts AC → QF)
│   ├── Historique des appels
│   └── Qualification
│       ├── Résultat appel
│       └── Relances planifiées
│
├── Opportunités
│   ├── Fiche opportunité
│   │   ├── Informations de l'entité ciblée
│   │   ├── Contexte et source de détection
│   │   └── Évaluation du potentiel
│   ├── Statuts (Nouveau → Qualifié → Converti / Perdu)
│   └── Conversion
│       ├── → Prospect (si qualifié, prise de contact engagée)
│       └── → Partenaire direct (si déjà signé, cas rare)
│
│   ├── Fiche client
│   ├── Parrainage
│   │   ├── Palier 50 € (×3)
│   │   └── Palier 100 € (×2)
│   └── Statut formation
│       ├── En formation
│       └── Terminé
│
├── Agenda / RDV Commerciaux
│   ├── Créer un RDV (sur site uniquement)
│   ├── Génération fiche récap PDF
│   └── Synchronisation calendriers
│       ├── Outlook (commerciaux)
│       └── Google Calendar (formateurs)
│
├── Outils transversaux
│   ├── Emails & Templates
│   │   ├── Template 1 — Confirmation RDV au CSE
│   │   └── Template 2 — Invitation agenda commercial
│   ├── Base de connaissances (module Documents)
│   │   ├── Procédures
│   │   ├── Scripts
│   │   ├── Objections / FAQ
│   │   ├── Modèles mails
│   │   └── Modèle fiche récap
│   └── Reporting & KPIs
│       ├── Tableaux de bord Direction
│       ├── Tableaux de bord Téléprospecteur
│       ├── Tableaux de bord Team Leader
│       └── Mail hebdomadaire automatisé (lundi 08h00)
│
└── Administration (accès Administrateur uniquement)
    ├── Gestion des utilisateurs & droits
    ├── Paramétrage des workflows
    ├── Gestion des templates
    ├── Configuration des imports Excel
    └── Gestion des dictionnaires (statuts, nomenclatures)
```

---

## 3. Architecture globale — Flux entre systèmes

```
┌─────────────────────────────────────────────────────────────┐
│                        Dolibarr                             │
│    Facturation · Paiements · Suivi pédagogique formateurs   │
└──────────────────────┬──────────────────────────────────────┘
                       │ Export Excel (chaque lundi)
                       │ Clients : nom, prénom, statut formation,
                       │ parrainages, partenaire d'origine
                       ▼
              ┌─────────────────┐
              │  Fichier Excel  │  ← Franck extrait depuis Dolibarr
              └────────┬────────┘
                       │ Import manuel ou script
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                      EspoCRM                                │
│           CRM Commercial AOPIA / LIKE Formation             │
│                                                             │
│  Partenaires · Prospects · Clients · RDV · Reporting        │
│  Base de connaissances · Emails · Workflows automatiques    │
└──────┬──────────────────────────────────────┬───────────────┘
       │ Sync bidirectionnelle                │ Sync bidirectionnelle
       ▼                                      ▼
┌─────────────┐                     ┌──────────────────┐
│   Outlook   │                     │ Google Calendar  │
│ Commerciaux │                     │   Formateurs     │
└─────────────┘                     └──────────────────┘

       │ Mail hebdo (lundi 08h00)
       ▼
┌──────────────────────────────────────────────────────────┐
│   Commerciaux — Email personnalisé par secteur           │
│   Partenaires actifs · Permanences · Clients parrainage  │
└──────────────────────────────────────────────────────────┘
```

| Flux | De | Vers | Mode | Fréquence |
|---|---|---|---|---|
| Clients / bénéficiaires | Dolibarr | EspoCRM | Export Excel + import | Chaque lundi |
| Nouveaux partenaires | Bruno (manuel) | EspoCRM | Saisie directe | Dès signature |
| Statuts partenaires | Dolibarr | EspoCRM | Export Excel | Hebdomadaire |
| RDV commerciaux | EspoCRM | Outlook | Sync bidirectionnelle | Temps réel |
| RDV formateurs | EspoCRM | Google Calendar | Sync bidirectionnelle | Temps réel |
| Mail hebdomadaire | EspoCRM | Commerciaux | Tâche planifiée | Lundi 08h00 |
| Historique phoning | EspoCRM uniquement | — | Pas de remontée | — |

---

## 4. Modules EspoCRM — Détail fonctionnel

### 4.1 Module Partenaires

#### Champs de la fiche partenaire

| Champ | Type | Obligatoire | Remarque |
|---|---|---|---|
| Nom du partenaire | Texte | Oui | Nomenclature imposée : ex. CSE Leroy Merlin La Rochelle |
| Type | Liste | Oui | CSE / Syndicat / Association-Club / Entreprise / Partenariat annulé |
| État / Statut | Liste | Oui | Voir états ci-dessous |
| Entreprise mère | Lien Compte | Non | Ex. Leroy Merlin (au-dessus du CSE) |
| Numéro SIRET/SIRENE | Texte | Non | |
| Adresse complète | Adresse | Oui | |
| Département | Texte | Oui | Pour filtrer par zone commerciale |
| Téléphone standard | Téléphone | Non | |
| Email général | Email | Non | |
| Commercial / Mandataire | Lien Utilisateur | Oui | |
| Date de convention | Date | Non | |
| Origine du contact | Liste | Non | Reprendre liste Dolibarr |
| Nombre de salariés | Nombre décimal | Non | |
| Parrain / Marraine | Texte riche HTML | Non | |
| Permanences | Texte / Date | Non | Prochaine et dernière |
| Documents joints | Pièces jointes | Non | Convention, FDR, etc. |
| Notes internes | Texte riche | Non | |

#### États des partenaires (5 statuts retenus)

| # | Statut | Description |
|---|---|---|
| 1 | **À prospecter** | Identifié, aucun contact effectué |
| 2 | **En cours de prospection** | Actions de phoning ou RDV en cours |
| 3 | **Signé accord cadre** | Accord de principe signé |
| 4 | **Convention engagement** | Convention formelle signée, partenaire actif |
| 5 | **Refus** | Hors cible ou refus définitif |

#### Bloc Dirigeant *(non obligatoire)*

Informations sur le dirigeant de l'entreprise mère (au-dessus du CSE ou du syndicat).

| Champ | Type | Obligatoire |
|---|---|---|
| Nom / Prénom du dirigeant | Texte | Non |
| Fonction | Texte | Non |
| Téléphone direct | Téléphone | Non |
| Email pro | Email | Non |
| Notes | Texte libre | Non |

#### Bloc CSE *(non obligatoire — affiché si Type = CSE)*

Informations spécifiques au Comité Social et Économique.

| Champ | Type | Obligatoire |
|---|---|---|
| Nom du secrétaire | Texte | Non |
| Prénom du secrétaire | Texte | Non |
| Téléphone secrétaire (direct / perso) | Téléphone ×2 | Non |
| Email secrétaire (pro / perso) | Email ×2 | Non |
| Nom du trésorier | Texte | Non |
| Prénom du trésorier | Texte | Non |
| Téléphone trésorier (direct / perso) | Téléphone ×2 | Non |
| Email trésorier (pro / perso) | Email ×2 | Non |
| Secrétaire adjoint (nom / prénom) | Texte ×2 | Non |
| Téléphone secrétaire adjoint | Téléphone | Non |
| Email secrétaire adjoint | Email | Non |
| Nombre d'élus au CSE | Nombre entier | Non |
| Date de fin de mandat | Date | Non |
| Existence juridique du CSE | Liste : Association / SARL / Informelle | Non |
| Notes CSE | Texte libre | Non |

#### Bloc Syndicat *(non obligatoire — affiché si Type = Syndicat)*

Informations spécifiques au syndicat représentatif.

| Champ | Type | Obligatoire |
|---|---|---|
| Nom de l'organisation syndicale | Texte | Non |
| Syndicat d'appartenance | Liste : CGT / CFDT / FO / CFE-CGC / CFTC / Autre | Non |
| Nom du responsable syndical | Texte | Non |
| Prénom du responsable syndical | Texte | Non |
| Fonction | Texte (ex. Délégué syndical, Responsable dép.) | Non |
| Téléphone (direct / perso) | Téléphone ×2 | Non |
| Email (pro / perso) | Email ×2 | Non |
| Périmètre géographique | Texte (ex. Région Ouest, Dép. 17) | Non |
| Notes syndicat | Texte libre | Non |

#### Contacts liés au partenaire *(illimité)*

En complément des blocs structurés ci-dessus, chaque partenaire peut avoir un nombre illimité de contacts libres associés (RH, responsable formation, autre interlocuteur…) :

| Champ | Type |
|---|---|
| Nom / Prénom | Texte |
| Fonction | Texte libre |
| Syndicat d'appartenance | Texte libre |
| Téléphone direct / personnel | Téléphone ×2 |
| Email pro / personnel | Email ×2 |
| Disponibilités / permanences | Texte |
| Notes | Texte libre |

#### Suivi des actions (onglet Historique)

| Champ | Valeurs |
|---|---|
| Type de RDV | Appel / Permanence / Présentation |
| Résultat du RDV | Réalisé / Annulé / Décalé |
| Date et heure | Date + heure |
| Commentaires | Texte libre |
| Commercial concerné | Lien utilisateur |

---

### 4.2 Module Prospects

Pipeline de prospection des CSE / syndicats / entreprises non encore partenaires.

#### Visibilité par profil

| Profil | Ce qu'il voit | Ce qu'il peut faire |
|---|---|---|
| **Téléprospecteur** | Sa liste filtrée : statuts AC et En cours (fiches affectées) | Appeler, saisir résultat, mettre à jour statut |
| **Commercial** | Sa liste filtrée : statuts À prospecter et En cours (son secteur) | Consulter, saisir notes, mettre à jour statut RDV |
| **Team Leader** | Toute la base, tous statuts, tous téléprospecteurs | Importer, affecter, superviser, **convertir en Partenaire** |
| **Administrateur** | Accès total | Accès total |

#### Règle de conversion Prospect → Partenaire

> **Seul le Team Leader peut convertir un prospect en partenaire.**
> La conversion se déclenche uniquement lorsque le statut atteint **« Convention signée »**.
> Le Team Leader crée alors la fiche Partenaire dans le module Partenaires et clôture la fiche prospect.

```
[Prospect] ──► statut "Convention signée" ──► Team Leader convertit
                                                       │
                                                       ▼
                                              [Partenaire créé]
                                       statut "Convention engagement"
```

#### Statuts du pipeline prospects

| Statut | Visible par | Description |
|---|---|---|
| **AC — À contacter** | Téléprospecteur (sa liste) | Fiche affectée, aucun appel passé |
| **En cours de prospection** | Téléprospecteur + Commercial | Appels en cours, pitch en cours |
| **RDV planifié** | Commercial (sa liste) | RDV commercial pris, QF validé |
| **Convention signée** | Team Leader | Accord signé — déclencheur de conversion en Partenaire |
| **Refus** | Team Leader | Hors cible ou refus définitif |

#### Autres règles

- Affectation d'une fiche : à un téléprospecteur ET à un commercial (deux champs distincts)
- Historique des appels avec résultat (STD-Joint, STD-NR, CSE-NR, KO, RPC, RP, QF…)
- Dès qu'un RDV est pris → statut « RDV planifié » → fiche sort du pipeline phoning, passe au commercial
- Le commercial ne peut pas créer de prospect ni convertir en partenaire

---

### 4.3 Module Opportunités

La fiche Opportunité représente une entité (entreprise, CSE, syndicat) identifiée comme **potentiellement intéressante**, avant même qu'une prise de contact soit engagée, ou lors d'une **toute première prise de contact non encore qualifiée**.

Elle sert de sas d'entrée entre la détection et la prospection active. Une opportunité qualifiée devient un Prospect ; une opportunité non pertinente est clôturée comme Perdue.

```
[Détection / signal faible]
          │
          ▼
  [Opportunité créée]  ←── Source : réseau, recommandation,
          │                         salon, phoning entrant,
          │                         client existant, LinkedIn…
          │
    ┌─────┴─────┐
    ▼           ▼
[Qualifiée]  [Perdue]
    │
    ▼
[Converti en Prospect]
 → Entre dans le pipeline phoning
```

#### Champs de la fiche Opportunité

| Champ | Type | Obligatoire | Remarque |
|---|---|---|---|
| Nom de l'entité ciblée | Texte | Oui | Raison sociale de l'entreprise ou du CSE |
| Type pressenti | Liste | Non | CSE / Syndicat / Entreprise / Inconnu |
| Département | Texte / Nombre | Non | Zone géographique |
| Téléphone standard | Téléphone | Non | Si connu |
| Email général | Email | Non | Si connu |
| Adresse | Adresse | Non | |
| SIRET | Texte | Non | 14 chiffres |
| Secteur d'activité | Texte | Non | Ex. Grande distribution, Industrie |
| Nombre de salariés (estimation) | Nombre | Non | Ordre de grandeur |
| Chiffre d'affaires (estimation) | Nombre | Non | |
| Source de détection | Liste | Oui | Voir valeurs ci-dessous |
| Détails de la source | Texte libre | Non | Ex. nom du client qui a recommandé |
| Potentiel estimé | Liste | Non | Faible / Moyen / Fort |
| Statut | Liste | Oui | Voir statuts ci-dessous |
| Assigné à | Lien Utilisateur | Non | Commercial ou téléprospecteur à contacter |
| Date de détection | Date | Oui | Auto-remplie à la création |
| Date de première prise de contact | Date | Non | Remplie lors du premier appel |
| Interlocuteur identifié (nom) | Texte | Non | Nom de la personne contactée |
| Interlocuteur identifié (fonction) | Texte | Non | Ex. Secrétaire CSE, DRH |
| Interlocuteur identifié (téléphone) | Téléphone | Non | |
| Interlocuteur identifié (email) | Email | Non | |
| Notes / contexte | Texte riche | Non | Tout ce qui a été collecté |
| Raison de perte | Liste | Non | Obligatoire si statut = Perdu |

#### Sources de détection (liste)

| Valeur | Description |
|---|---|
| Réseau commercial | Recommandation d'un commercial de l'équipe |
| Client existant | Un bénéficiaire a mentionné son CSE |
| Parrainage / recommandation | Un partenaire ou client a recommandé |
| Phoning entrant | L'entité a contacté AOPIA / LIKE de son propre chef |
| Salon / événement | Rencontré lors d'un salon professionnel |
| LinkedIn / réseaux sociaux | Détecté via prospection digitale |
| Fichier externe | Issu d'un fichier achat, top 500, INSEE… |
| Autre | À préciser dans les notes |

#### Statuts de la fiche Opportunité

| Statut | Description | Transition possible |
|---|---|---|
| **Nouveau** | Entité identifiée, aucune action engagée | → En cours d'évaluation |
| **En cours d'évaluation** | Première prise de contact ou collecte d'infos | → Qualifiée ou Perdue |
| **Qualifiée** | Potentiel confirmé, prête à entrer en prospection active | → Converti en Prospect |
| **Converti** | Transformée en fiche Prospect dans le pipeline phoning | — (fiche archivée) |
| **Perdue** | Non pertinente, hors cible ou doublon | — (raison obligatoire) |

#### Visibilité et droits par profil

| Profil | Ce qu'il voit | Ce qu'il peut faire |
|---|---|---|
| **Téléprospecteur** | Les opportunités qui lui sont assignées | Créer, saisir 1er contact, mettre à jour statut |
| **Commercial** | Les opportunités de son secteur | Créer, qualifier, enrichir les informations |
| **Team Leader** | Toutes les opportunités | Créer, assigner, convertir en Prospect, clôturer |
| **Administrateur** | Accès total | Accès total |

#### Règle de conversion Opportunité → Prospect

> La conversion est possible depuis les statuts **Qualifiée** uniquement.
> Elle peut être réalisée par le **Commercial** ou le **Team Leader**.
> À la conversion, les informations de la fiche Opportunité sont reprises dans la nouvelle fiche Prospect (raison sociale, coordonnées, interlocuteur, source, notes).
> La fiche Opportunité passe automatiquement au statut **Converti** et est archivée.

---

### 4.4 Module Clients (Bénéficiaires)

Base issue de Dolibarr, importée via export Excel hebdomadaire (chaque lundi).

| Champ | Type | Source |
|---|---|---|
| Nom / Prénom | Texte | Dolibarr |
| Date de naissance | Date | Dolibarr |
| Adresse complète | Adresse | Dolibarr |
| Téléphone | Téléphone | Dolibarr |
| Email | Email | Dolibarr |
| Partenaire / CSE d'origine | Lien Partenaire | Dolibarr |
| Statut formation | En formation / Terminé | Dolibarr |
| Nombre d'heures de formation | Nombre | Dolibarr |
| Nombre de parrainages réalisés | Nombre | Dolibarr |
| Palier parrainage | Calculé : 1-3 = 50 € / 4-5 = 100 € | CRM |
| Commercial assigné | Lien Utilisateur | CRM |
| Notes commerciales | Texte riche | CRM |

> **Prix de formation, facturation et données bancaires : non transférés dans EspoCRM.**

---

### 4.4 Module Agenda / RDV

- Gestion des RDV commerciaux liés aux partenaires et prospects
- Synchronisation avec Outlook (commerciaux) et Google Calendar (formateurs)
- Types de RDV : Appel / Permanence (sur site uniquement, pas de distanciel) / Présentation
- Deux créneaux proposés automatiquement lors de la prise de RDV
- Génération automatique du fichier récap PDF après saisie complète

---

### 4.5 Module Emails & Templates

| Template | Déclencheur | Destinataire |
|---|---|---|
| Template 1 — Confirmation RDV | RDV créé / confirmé | Interlocuteur CSE |
| Template 2 — Invitation agenda | Après génération PDF récap | Commercial + CC Bruno & Nérina |
| Mail hebdomadaire | Chaque lundi 08h00 | Chaque commercial (email individuel) |

---

### 4.6 Module Base de Connaissances

Module Documents EspoCRM avec l'arborescence suivante :

```
Base de connaissances
├── Procédures
├── Scripts (phoning, présentation)
├── Objections / FAQ
├── Modèles mails
└── Modèle fiche récap
```

---

## 5. Workflow Phoning CSE

```
┌─────────────────────────────────────────────────────────────────────┐
│  ÉTAPE 1 — Import & affectation                                     │
│  Team Leader : import base Excel, segmentation, affectation         │
│  Statut initial : AC (À Contacter)                                  │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ÉTAPE 2 — Appel du standard (OBLIGATOIRE)                          │
│  Téléprospecteur : appel numéro principal, demande secrétaire/CSE   │
│  Max 3 tentatives à horaires différents                             │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
               ┌───────────────┼───────────────┬──────────────┐
               ▼               ▼               ▼              ▼
          STD-Joint         STD-NR          CSE-NR            KO
     (infos obtenues)  (3 tentatives      (absent /      (hors cible)
                         sans rép.)        réunion)
               │               │               │
               │         ↻ Relance J+2         │
               └───────────────┴───────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ÉTAPE 3 — Échange avec le CSE (pitch)                              │
│  Mise en relation · Découverte · Présentation · Traitement objections│
└──────────────────────────────┬──────────────────────────────────────┘
                               │
               ┌───────────────┼───────────────┐
               ▼               ▼               ▼
              RPC              RP           KO ferme
       (intérêt, pas    (rappel planifié   (refus définitif)
        de date)         avec créneau)
               │               │
               └───────────────┘
               Si accord RDV ↓
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ÉTAPE 4 — Prise de RDV (< 30 minutes)                             │
│  5.1 Calage RDV + création ticket CRM                               │
│  5.2 Remplissage fiche obligatoire complète                         │
│  5.3 Mail 1 : confirmation RDV au CSE (Template 1)                  │
│  5.4 Génération automatique fiche récap PDF                         │
│  5.5 Mail 2 : invitation agenda au commercial + CC Bruno & Nérina   │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ÉTAPE 5 — Validation Team Leader                                   │
│  Contrôle qualité des 7 éléments obligatoires → passage en QF       │
└─────────────────────────────────────────────────────────────────────┘
```

### Codes statuts de qualification

| Code | Statut | Définition |
|---|---|---|
| AC | À Contacter | Fiche nouvellement attribuée, aucun appel effectué |
| STD-NR | Standard Non Répondu | Aucune réponse du standard après 3 tentatives |
| STD-Joint | Standard Joint | Standard répondu, toutes les infos pas encore obtenues |
| CSE-NR | CSE Non Joint | Standard répond mais CSE indisponible |
| RP | Rappel Planifié | Créneau précis obtenu pour rappeler le CSE |
| RPC | RDV à Planifier | CSE joint, intérêt exprimé, date non fixée |
| KO | Hors Cible / Refus | Refus ferme, hors cible, effectif insuffisant, n° invalide |
| QF | RDV Qualifié | RDV confirmé et validé par le Team Leader |

### 7 éléments bloquants pour le passage en QF

| # | Élément | Qui le fournit |
|---|---|---|
| 1 | RDV créé dans le CRM (date, heure, lieu sur site) | Téléprospecteur |
| 2 | Email de confirmation envoyé au CSE | Automatique (Template 1) |
| 3 | Tous les champs obligatoires de la fiche renseignés | Téléprospecteur |
| 4 | Fichier récap PDF généré | Automatique |
| 5 | Enregistrement audio de la conversation confirmant le RDV | Téléprospecteur |
| 6 | Email invitation agenda envoyé au commercial | Automatique (Template 2) |
| 7 | Validation Team Leader effectuée | Team Leader |

### Règle des 3 tentatives

- Maximum 3 tentatives à des horaires différents (matin / midi / après-midi) avant STD-NR
- Si aucune réponse après 3 tentatives → STD-NR + rappel automatique optionnel J+2-1
- Chaque tentative doit être enregistrée dans le CRM avec date, heure et résultat

---

## 6. Droits utilisateurs — Matrice complète

### Profils et périmètres

| Module / Fonctionnalité | Téléprospecteur | Team Leader | Commercial | Administrateur |
|---|:---:|:---:|:---:|:---:|
| **Sa portefeuille de fiches** | ✅ Complet | ✅ Complet | ❌ | ✅ Total |
| **Prospects — liste AC / En cours** | ✅ Sa liste (affectées) | ✅ Toute la base | ✅ Sa liste (son secteur) | ✅ Total |
| **Prospects — saisie résultat appel** | ✅ Oui | ✅ Oui | ❌ | ✅ Total |
| **Prospects — MAJ statut RDV** | ❌ | ✅ Oui | ✅ Oui | ✅ Total |
| **Prospects — conversion en Partenaire** | ❌ | ✅ Si "Convention signée" | ❌ | ✅ Total |
| **Créer un appel / RDV** | ✅ Oui | ✅ Oui | ⚠️ MAJ statut seul | ✅ Total |
| **Envoi emails depuis CRM** | ✅ Oui | ✅ Oui | ❌ | ✅ Total |
| **Fiche partenaire (détail)** | ⚠️ Lecture seule | ✅ Complet | ⚠️ Si affecté | ✅ Total |
| **Liste de tous les partenaires** | ⚠️ Liste seule | ✅ Complet | ⚠️ Liste seule | ✅ Total |
| **Fiches clients / bénéficiaires** | ❌ | ⚠️ Lecture | ⚠️ Si envoyé | ✅ Total |
| **Validation QF** | ❌ | ✅ Oui | ❌ | ✅ Total |
| **Import base de données** | ❌ | ✅ Oui | ❌ | ✅ Total |
| **Supervision et reporting** | ❌ | ✅ Oui | ❌ | ✅ Total |
| **Paramétrage CRM** | ❌ | ❌ | ❌ | ✅ Total |
| **Base de connaissances (lecture)** | ✅ Lecture | ✅ Complet | ❌ | ✅ Total |
| **Base de connaissances (édition)** | ❌ | ✅ Oui | ❌ | ✅ Total |
| **Onglet suivi partenaire (saisie)** | ✅ Oui | ✅ Oui | ✅ Si affecté | ✅ Total |
| **Génération PDF récap** | ✅ Auto | ✅ Auto | ❌ | ✅ Total |
| **Mail hebdomadaire** | ❌ | ✅ Voir tous | ✅ Reçoit | ✅ Total |

**Légende :** ✅ Accès complet · ⚠️ Accès conditionnel ou partiel · ❌ Refusé

### Règles d'accès aux partenaires (niveaux 1 et 2)

**Niveau 1 — Lecture :**
Le commercial peut voir la liste des partenaires et les fiches, mais sans modification ni création. Il peut accéder à l'onglet Suivi et y réaliser des saisies.

**Niveau 2 — Édition :**
Accès complet uniquement si le commercial est affecté en qualité de commercial/mandataire sur le tiers partenaire (champ `commercial/mandataire` renseigné sur la fiche).

### Détail par profil

**Téléprospecteur**
- Voit sa liste de prospects filtrée : statuts AC et En cours de prospection (fiches affectées)
- Création et mise à jour des appels et RDV
- Envoi d'emails depuis le CRM (Templates 1 et 2)
- Accès en lecture à la Base de Connaissances
- Saisie dans l'onglet Suivi des partenaires
- Ne peut pas convertir un prospect en partenaire

**Team Leader**
- Voit toute la base prospects, tous statuts, tous téléprospecteurs
- Accès complet à toutes les campagnes
- Import de base et affectation des fiches aux téléprospecteurs
- Validation des RDV (passage en statut QF)
- **Seul habilité à convertir un prospect en Partenaire** (déclencheur : statut « Convention signée »)
- Supervision et reporting complet

**Commercial (Responsable de Secteur)**
- Voit sa liste de prospects filtrée : statuts À prospecter et En cours (son secteur géographique)
- Accès uniquement aux RDV qui lui sont envoyés par invitation agenda
- Peut mettre à jour le statut RDV après réalisation
- Consultation des fiches et documents (fiche récap + audio)
- Accès à la fiche partenaire uniquement s'il y est affecté
- Ne peut pas convertir un prospect en partenaire
- Consultation des fiches et documents (fiche récap + audio)
- Mise à jour du statut RDV après réalisation
- Accès à la fiche partenaire uniquement s'il y est affecté

**Administrateur**
- Accès total à toutes les fonctionnalités
- Paramétrage : utilisateurs, templates, workflows, dashboards, droits
- Gestion des règles de sécurité et des dictionnaires

---

## 7. Automatisations et workflows

| Workflow | Déclencheur | Condition | Action | Destinataire |
|---|---|---|---|---|
| WF1 — Confirmation RDV CSE | RDV créé / confirmé | Statut = prise de RDV | Envoi Template 1 | Interlocuteur CSE |
| WF2 — Génération PDF récap | Saisie complète | Tous champs obligatoires renseignés | Génération PDF | Stocké dans CRM |
| WF3 — Invitation agenda | Après génération PDF | PDF + audio disponibles | Envoi Template 2 avec PDF + audio | Commercial + CC Bruno & Nérina |
| WF4 — Blocage QF | Tentative passage QF | Champs manquants | Blocage + message d'erreur | Téléprospecteur |
| WF5 — Reporting hebdo Phoning | Chaque lundi 07h30 | Aucune | Mail récap personnalisé par téléprospecteur | Chaque téléprospecteur + Team Leader |
| WF6 — Reporting hebdo Commerciaux | Chaque lundi 08h00 | Aucune | Mail récap personnalisé par commercial | Chaque commercial + Team Leader |
| WF7 — Rappel RP | Statut RP avec date/heure | Créneau planifié | Création tâche rappel | Téléprospecteur |

---

## 7bis. Reporting hebdomadaire — Détail des 2 services

Deux reportings distincts, envoyés chaque lundi matin. Chaque destinataire reçoit uniquement ses propres données. Le Team Leader reçoit les deux, consolidés.

---

### Reporting Phoning — Service Téléprospection

**Destinataires :** chaque téléprospecteur (ses données uniquement) + Team Leader (toutes les données)
**Envoi :** chaque lundi à 07h30
**Format :** dashboard CRM dédié + mail récap PDF

#### Dashboard CRM — Téléprospecteur

Affiché à la connexion, données de la semaine écoulée (lun → ven) :

| Indicateur | Description |
|---|---|
| Appels du jour | Nombre d'appels passés aujourd'hui |
| Appels de la semaine | Total sur les 5 derniers jours ouvrés |
| Rappels du jour (RP) | Fiches avec RP planifié à la date du jour |
| CSE joints | Nombre de CSE effectivement contactés |
| RDV QF validés | Nombre de RDV passés en statut QF cette semaine |
| Taux de conversion | CSE joints / appels passés (%) |
| Statuts par étape | Répartition AC / STD-NR / STD-Joint / CSE-NR / RP / RPC / KO / QF |
| Base restante à contacter | Nombre de fiches en statut AC encore non traitées |

#### Mail récap hebdomadaire — Téléprospecteur

```
REPORTING PHONING — Semaine du [DATE] au [DATE]
Téléprospecteur : [Prénom Nom]
────────────────────────────────────────────
ACTIVITÉ DE LA SEMAINE
  Appels passés          : [N]
  CSE joints             : [N]
  RDV QF validés         : [N]
  Taux de conversion     : [N]%

STATUTS EN COURS
  AC — À contacter       : [N] fiches restantes
  RP — Rappels planifiés : [N]  (dont [N] aujourd'hui)
  RPC — À planifier      : [N]

RÉSULTATS
  STD-NR                 : [N]
  KO / Refus             : [N]

PROCHAINS RAPPELS (cette semaine)
  [Date] [Heure] — [Nom prospect] — [Téléphone]
  ...
────────────────────────────────────────────
```

---

### Reporting Commerciaux — Service Commercial

**Destinataires :** chaque commercial (ses données uniquement) + Team Leader (toutes les données)
**Envoi :** chaque lundi à 08h00
**Format :** dashboard CRM dédié + mail récap PDF

#### Dashboard CRM — Commercial

Affiché à la connexion, données de la semaine écoulée + agenda à venir :

| Indicateur | Description |
|---|---|
| Partenaires actifs | Liste de ses partenaires avec statut et prochaine permanence |
| RDV de la semaine passée | RDV réalisés / annulés / décalés |
| RDV de la semaine à venir | Agenda des 5 prochains jours ouvrés |
| Taux no-show / annulation | Nombre de RDV non honorés vs total |
| 20 derniers prospects | Ses prospects avec statut actuel |
| Clients parrainage actif | Clients ayant un palier de parrainage à activer |
| Pipeline RDV | RP / RPC en attente de planification |

#### Mail récap hebdomadaire — Commercial

```
REPORTING COMMERCIAL — Semaine du [DATE] au [DATE]
Commercial : [Prénom Nom] — Secteur : [Dép.]
────────────────────────────────────────────
PARTENAIRES ACTIFS ([N] total)
  [Nom partenaire] — [Statut] — Prochaine perm. : [Date]
  ...

RDV SEMAINE PASSÉE
  Réalisés   : [N]
  Annulés    : [N]
  Décalés    : [N]
  No-show    : [N]

RDV SEMAINE À VENIR
  [Date] [Heure] — [Nom partenaire] — [Lieu]
  ...

PROSPECTS EN ATTENTE
  RP / RPC à relancer    : [N]
  Nouveaux prospects     : [N]

CLIENTS — PARRAINAGE À ACTIVER
  [Nom client] — [N] parrainages — Prochain palier : [Montant €]
  ...
────────────────────────────────────────────
```

---

### Reporting consolidé Team Leader

Le Team Leader reçoit les deux mails (07h30 + 08h00) avec une vue agrégée sur l'ensemble des équipes :

| Vue | Contenu |
|---|---|
| Phoning consolidé | Performance de chaque téléprospecteur côte à côte, total équipe |
| Commerciaux consolidé | Pipeline RDV par commercial, taux no-show global |
| Alertes | Téléprospecteurs sans appel depuis 2 jours, RPC > 5 jours sans suite, RP non traités |

---

## 8. Cycle de vie d'un partenaire

```
[Import Excel] ──► [À prospecter] ──► [En cours de prospection]
                                               │
                    ┌──────────────────────────┼──────────────────────┐
                    │                          │                      │
                    ▼                          ▼                      ▼
           [Signé accord cadre]      [Convention engagement]       [Refus]
                    │                          │                      │
                    └──────────────────────────┘                      │
                                 │                                    │
                    [Convention engagement]                           │
                                 │                                    │
                          [Stop / inactif] ──────────────────────────►┘
                                                                      │
                                              ┌───────────────────────┘
                                              │ (reprise possible)
                                              ▼
                                      [À prospecter]
```

| État | Description | Déclencheur de transition |
|---|---|---|
| À prospecter | Identifié, aucun contact effectué | Premier appel passé |
| En cours de prospection | Phoning actif, plusieurs appels enregistrés | Résultat de l'échange CSE |
| Signé accord cadre | Accord de principe signé | Signature accord |
| Convention engagement | Partenaire actif, permanences en cours | Signature convention |
| Refus | Hors cible, refus ferme ou partenariat arrêté | Décision |

---

## 9. Règles de transition entre statuts

| Statut source | Statut cible | Condition requise | Champs obligatoires |
|---|---|---|---|
| AC | En cours | Au moins 1 appel enregistré | Date, heure, résultat |
| En cours | STD-NR | 3 tentatives à horaires distincts | 3 entrées historique |
| En cours | CSE-NR | Standard joint, CSE absent | Nom standard si obtenu |
| En cours / RP / RPC | Prise de RDV | Accord verbal CSE obtenu | Date RDV, lieu, nom interlocuteur |
| Prise de RDV | QF | Tous les 7 éléments présents | PDF, audio, emails, validation TL |
| Tout statut | KO | Aucune condition bloquante | Motif KO (liste obligatoire) |
| Refus | À prospecter | Décision de réactivation | Note de reprise de contact |

---

## 10. Synchronisation Dolibarr → EspoCRM

### Mapping des champs clients — issu des fichiers Excel réels

Le fichier d'export Dolibarr contient plusieurs feuilles selon la société. Les champs ci-dessous sont extraits des feuilles `crm propos like`, `crm propos aopia-abo`, `crm clients like` et `crm clients aopia-abo`.

#### Champs importés dans EspoCRM (clients / bénéficiaires)

| Champ Dolibarr (Excel) | Champ EspoCRM | Type | Importé | Remarque |
|---|---|---|:---:|---|
| Civilité | salutation | Liste | ✅ | M. / Mme |
| Tiers / Nom du tiers | lastName + firstName | Texte | ✅ | Séparation Nom / Prénom à prévoir |
| Réf. client | customField_refClient | Texte | ✅ | Référence unique Dolibarr |
| Date de naissance | birthDate | Date | ✅ | Format à normaliser |
| Téléphone | phoneNumber | Téléphone | ✅ | |
| Email | emailAddress | Email | ✅ | |
| Adresse | billingAddressStreet | Texte | ✅ | |
| Code postal | billingAddressPostalCode | Texte | ✅ | |
| Ville | billingAddressCity | Texte | ✅ | |
| Partenaire Like / Partenaire Like et Aopia | accountId | Lien Partenaire | ✅ | Matching par nomenclature exacte |
| Suivi actuel du client | customField_suiviActuel | Texte | ✅ | Nom du commercial/formateur suivi |
| Entreprise / Salarié(e) de | customField_entreprise | Texte | ✅ | Entreprise employeur du bénéficiaire |
| État | customField_statutFormation | Liste | ✅ | À venir / En cours / Terminé |
| (F) Date de lancement | customField_dateLancement | Date | ✅ | |
| (C) Date début de formation | customField_dateDebutFormation | Date | ✅ | |
| (C) Date de fin formation théorique | customField_dateFinFormation | Date | ✅ | |
| Nombre d'heures de formation | customField_heuresFormation | Nombre | ✅ | Total contractualisé |
| Heures réalisées | customField_heuresRealisees | Nombre | ✅ | |
| Heures restantes | customField_heuresRestantes | Nombre | ✅ | Calculé |
| Evaluation initiale | customField_evaluationInitiale | Booléen/Note | ✅ | |
| Date certification | customField_dateCertification | Date | ✅ | |
| NOM PRENOM (parrain) | customField_parrainNom | Texte | ✅ | Bloc parrainage |
| Tél (parrain) | customField_parrainTel | Téléphone | ✅ | |
| email (parrain) | customField_parrainEmail | Email | ✅ | |
| Adresse postale (parrain) | customField_parrainAdresse | Texte | ✅ | |
| Super parrain | customField_superParrain | Booléen | ✅ | Programme Super Parrain |
| Ne plus contacter | customField_nePlusContacter | Booléen | ✅ | Filtre obligatoire à l'import |
| Montant CPF | — | — | ❌ | **NON TRANSFÉRÉ** — donnée financière |
| Consultant 1er accueil | — | — | ❌ | Non pertinent CRM commercial |
| Consultant Formateur | — | — | ❌ | Reste dans Dolibarr |
| Contact Pole ADV | — | — | ❌ | Reste dans Dolibarr |
| PDF | — | — | ❌ | Document interne Dolibarr |
| Heures de formation obligatoires | — | — | ❌ | Détail pédagogique, non nécessaire |
| Heures de formation complémentaires | — | — | ❌ | Détail pédagogique, non nécessaire |
| Avis Google | — | — | ❌ | Non pertinent CRM commercial |
| Date envoi questionnaire à chaud | — | — | ❌ | Suivi pédagogique, reste Dolibarr |

#### Règles de gestion des doublons à l'import

- **Clé de déduplication :** `Réf. client` (identifiant unique Dolibarr) → si existant, mise à jour ; sinon création
- **Fallback si Réf. absente :** Nom + Prénom + Date de naissance
- **Rattachement partenaire :** le champ `Partenaire Like et Aopia` doit correspondre exactement à la nomenclature EspoCRM (ex. `CSE Leroy Merlin La Rochelle`)
- **Clients sans partenaire :** importés avec statut `Partenaire non rattaché` → traitement manuel par Bruno
- **Ne plus contacter = 1 :** ces fiches sont importées mais marquées inactives — elles n'apparaissent pas dans les listes de phoning

---

### Mapping des champs Partenaires / Prospects — issu du fichier phoning Excel

Le fichier `Fichier_top_500_département` correspond à la base de prospection utilisée par les téléprospecteurs. Il alimente le module **Prospects** d'EspoCRM.

#### Structure du fichier phoning (feuille `Dpt 44_BIRE`)

| Groupe | Champ Excel | Champ EspoCRM | Type | Importé | Remarque |
|---|---|---|---|:---:|---|
| **Prospection** | Conseiller | assignedUserId | Lien Utilisateur | ✅ | Téléprospecteur affecté |
| **Prospection** | Dpt | customField_departement | Texte | ✅ | Ex. La Loire-Atlantique |
| **Prospection** | Etat | status | Liste | ✅ | AC / En cours / RPC / RP / KO / QF |
| **Prospection** | Date de 1er contact | customField_datePremierContact | Date | ✅ | |
| **Prospection** | Commentaires / situation actuelle | description | Texte riche | ✅ | |
| **Infos générales** | Raison sociale | name | Texte | ✅ | Nom du prospect / partenaire |
| **Infos générales** | Adresse | billingAddressStreet | Texte | ✅ | |
| **Infos générales** | CP | billingAddressPostalCode | Texte | ✅ | |
| **Infos générales** | Ville | billingAddressCity | Texte | ✅ | |
| **Infos générales** | Téléphone 1 | phoneNumber | Téléphone | ✅ | Standard principal |
| **Infos générales** | Téléphone 2 | phoneNumberAlt | Téléphone | ✅ | Numéro secondaire |
| **Infos générales** | Nbrs de salariés | customField_nbSalaries | Nombre | ✅ | |
| **Infos générales** | Secteur d'activités | customField_secteurActivite | Texte | ✅ | Code NAF / libellé |
| **Infos générales** | CA | customField_chiffreAffaires | Nombre | ✅ | Chiffre d'affaires annuel |
| **Infos générales** | Siret | customField_siret | Texte | ✅ | 14 chiffres |

#### Champs EspoCRM à ajouter non présents dans le fichier Excel

Ces champs seront renseignés manuellement ou automatiquement dans EspoCRM après import :

| Champ EspoCRM | Source | Remarque |
|---|---|---|
| Type (CSE / Syndicat / Entreprise…) | Manuel | À qualifier lors du premier appel |
| Nom du CSE / interlocuteur | Manuel | Collecté lors de l'appel standard |
| Email général CSE | Manuel | Collecté lors de l'appel |
| Permanences (prochaine / passée) | Manuel | Saisie après accord |
| Commercial assigné | Manuel | Affecté par le Team Leader après QF |
| Date de convention | Manuel | Après signature |
| Bloc Dirigeant / CSE / Syndicat | Manuel | Enrichi progressivement |

#### Procédure d'import hebdomadaire (chaque lundi)

| Étape | Qui | Action |
|---|---|---|
| 1 | Franck | Extraire les clients depuis Dolibarr (exclure les champs non transférés) |
| 2 | Franck | Envoyer le fichier Excel (email ou dossier partagé) |
| 3 | Rizo | Lancer l'import dans EspoCRM via template de mapping prédéfini |
| 4 | Rizo | Contrôler le rapport d'import (nb créés, nb mis à jour, nb erreurs, nb `Ne plus contacter`) |
| 5 | Bruno | Traiter manuellement les clients `Partenaire non rattaché` |
| 6 | Rizo | Valider l'import et confirmer à Alexandre |

#### Nomenclature des partenaires (règle imposée)

Format : `[Type d'entité] [Nom de l'entreprise] [Ville]`

Exemples issus du fichier Excel :
- `CSE Leroy Merlin La Rochelle`
- `CSE Airbus Rochefort`
- `LARNAY SAGESSE BIARD 86 CSE` *(format actuel Dolibarr — à normaliser)*

> Le champ `Partenaire Like` dans Dolibarr doit être identique au champ `name` du partenaire dans EspoCRM pour garantir le matching automatique à l'import.

---

## 11. Contenu du fichier récap PDF

Généré automatiquement après saisie complète des champs obligatoires.

| Section | Champs inclus |
|---|---|
| Informations entreprise | Raison sociale, secteur, effectif, adresse, téléphone, responsable secteur, commercial assigné |
| Interlocuteur CSE | Nom, prénom, fonction, téléphone direct, email, disponibilités, notes |
| Contexte de l'échange | Date/heure appel, étape d'accès, besoins CSE, réponses, objections, points d'attention |
| RDV commercial | Date/heure, lieu (sur site), adresse, nom du commercial, email de confirmation |
| Qualification et suivi | Statut actuel, date qualification, téléprospecteur, nb tentatives, prochain rappel |
| Pièces jointes | Enregistrement audio, email confirmation CSE, email invitation agenda |

---

## 12. Tableaux de bord et reporting

Quatre dashboards distincts dans EspoCRM, chacun affiché à la connexion selon le profil de l'utilisateur.

### Dashboard Direction

- Derniers clients des 3 derniers mois
- 10 derniers partenaires signés (accord cadre ou convention engagement)
- Super Parrains / Super Marraines (clients multi-parrainage)
- 20 derniers prospects
- Dernières permanences
- RDV par département / commercial
- Pipeline RDV global et taux de transformation

### Dashboard Téléprospecteur *(voir détail section 7bis)*

- Appels du jour / semaine
- Rappels du jour (RP planifiés)
- CSE joints / RDV QF validés
- Taux de conversion (CSE joints / appels)
- Répartition des statuts par étape
- Base restante à contacter

### Dashboard Commercial *(voir détail section 7bis)*

- Partenaires actifs avec statut et prochaine permanence
- RDV semaine passée (réalisés / annulés / décalés / no-show)
- RDV semaine à venir
- 20 derniers prospects et statut
- Clients avec parrainage à activer
- Pipeline RP / RPC en attente

### Dashboard Team Leader

- Performance par téléprospecteur (appels, QF, taux de conversion)
- Base attribuée / restante par téléprospecteur
- RP / RPC en attente sur l'ensemble de l'équipe
- Taux no-show / annulation par commercial
- Validation QF en attente
- Alertes : téléprospecteurs sans appel depuis 2 jours, RPC > 5 jours sans suite

---

## 13. Exigences techniques

| Critère | Exigence |
|---|---|
| Solution | EspoCRM (self-hosted, open source) |
| Hébergement | Serveur propre (maîtrise des données, moindre coût) |
| Accès | Interface web responsive (desktop + mobile) |
| Langue | Français |
| Simplicité | Interface intuitive pour commerciaux non-techniciens |
| Sync agendas | Outlook (commerciaux) + Google Calendar (formateurs) |
| Génération PDF | Fiche récap auto-générée après saisie complète |
| Enregistrement audio | Pièce jointe liée au RDV |
| Export Excel | Possible depuis toutes les listes |
| Mail automatisé | Tâches planifiées (hebdo, rappels) |
| Multi-sociétés | À prévoir pour AOPIA et AloPro (même outil) |
| Facturation / pédagogie | Exclus du périmètre, restent dans Dolibarr |

---

## 14. Planning et priorités

| Priorité | Livrable | Responsable | Échéance |
|---|---|---|---|
| P1 — Immédiat | Configuration EspoCRM (modules, champs, statuts) | Rizo | Mardi/Mercredi |
| P1 — Immédiat | Import base partenaires (fichier Excel Bruno) | Rizo + Bruno | Mardi/Mercredi |
| P1 — Immédiat | Import base clients (extraction Dolibarr via Franck) | Rizo + Franck | Lundi suivant |
| P1 — Immédiat | Statuts partenaires + pipeline phoning opérationnel | Rizo | Mardi/Mercredi |
| P2 — Semaine 1 | Templates emails (Template 1 + Template 2) | Rizo + Alexandre | Après réception templates |
| P2 — Semaine 1 | Génération fiche récap PDF | Rizo | Semaine 1 |
| P2 — Semaine 1 | Synchronisation agendas Outlook / Google | Rizo | Semaine 1 |
| P3 — Semaine 2 | Dashboards et reporting | Rizo | Semaine 2 |
| P3 — Semaine 2 | Mail hebdomadaire automatisé | Rizo | Semaine 2 |
| P3 — Semaine 2 | Formation des équipes (demi-journée) | Rizo | Avant mise en production |
| P4 — Mois 2 | Liaison automatique imports Dolibarr → EspoCRM | Rizo | À planifier |
| P4 — Mois 2 | Intégration AloPro sur le même CRM | Rizo + équipe Opia | À planifier |

---

## 15. Validation

> Après validation du présent cahier des charges, toute demande supplémentaire fera l'objet d'un avenant.

| Signataire | Fonction | Date | Signature |
|---|---|---|---|
| Alexandre FLOREK | Co-Gérant, AOPIA / LIKE Formation | | |
| Bruno BIARDOUX | Responsable Partenariats | | |
| Franck PINO CORTES | Responsable ADV / RC | | |
| Rizo | Développeur CRM | | |

**Mention « Bon pour accord »**

---

*Document généré — Mai 2026 — Version 1.0*
