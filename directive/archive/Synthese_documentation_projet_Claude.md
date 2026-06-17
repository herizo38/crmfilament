# Synthèse et Documentation de Projet — AlloPro 24/24

> Source : conversation Claude du 02/06/2026
> Documents sources : 6 fichiers DOCX (scripts appel, pipeline CRM, présentation service, flux de travail, procédure traitement, guide télépros)

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Flux opérationnel P1 → P8](#flux-opérationnel-p1--p8)
3. [Pipeline CRM — Cycle de vie d'un dossier](#pipeline-crm--cycle-de-vie-dun-dossier)
4. [Dictionnaire de données (Entités)](#dictionnaire-de-données)
5. [Entités financières (Devis / BC / Facture)](#entités-financières)
6. [Workflows appel sortant](#workflows-appel-sortant)

---

## Vue d'ensemble

![Schéma AlloPro](images_synthese_pdf/page1_img1.png)

Le projet AlloPro 24/24 est un centre de contact externalisé pour artisans (12 corps de métier), opérant 24h/24 et 7j/7. La synthèse couvre :

1. **Requirements** — Exigences fonctionnelles : service entrant (particuliers), service sortant (téléprospection), CRM (14 statuts, champs obligatoires, automatisations), humain (3 rôles, règles non négociables), et 12 KPI avec cibles chiffrées.
2. **Design** — Architecture des 8 processus P1→P8, pipeline CRM complet, design du canal de vente (segmentation, tunnel 6 étapes, codification CRM campagne).
3. **Task List** — 8 phases de déploiement : configuration CRM (sem. 1–2) au déploiement général (mois 2), formation, automatisations, campagne de téléprospection.
4. **Schématisations** — Flux opérationnel P1→P8 en swimlane, pipeline CRM avec 14 statuts, branches urgence/fiche incomplète, SLA et KPI cibles.

---

## Flux opérationnel P1 → P8

### Swimlane : Particulier / Téléopérateur N1 / Back-Office / Artisan

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ P1 — ACCUEIL                                                                │
│  Appel entrant → Particulier décroche → Accueil + ID client                 │
│  Script P1 — CRM popup                                                      │
│  Urgence ?                                                                   │
│    OUI → P5 — Urgence (artisan urgence)                                     │
│    NON → P2                                                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│ P2 — QUALIFICATION                                                           │
│  Grille métier P2 — 12 scripts — arbre CRM                                  │
│  Niveau priorité : Urgence / Prioritaire / Standard                          │
│  Ticket CRM complet — Zéro champ manquant                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│ P3 — PRISE DE RDV                                                            │
│  RDV ou rappel — Créneau / Mode dégradé                                    │
│  SMS confirmation Client — ≤ 5 min                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│ P4 — ALERTE ARTISAN                                                          │
│  Notification artisan : SMS + fiche structurée                               │
│  Artisan répond ?                                                            │
│    NON → Relance Back-Office (SLA non respecté)                             │
│    OUI → RDV Confirmé                                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│ P5 — URGENCE (si déclenchée)                                                │
│  Artisan de garde — Contact direct SMS + appel                               │
│  Suivi jusqu'à résolution                                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│ DEVIS / BC / FACTURE                                                         │
│  Devis émis par artisan → Accepté ? → Bon de commande auto                 │
│  Acompte ? → Intervention réalisée → Facture émise (PDF auto)              │
│  Payé ? → Relance impayé si non réglé                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ P6 — POST-INTERVENTION                                                       │
│  Intervention réalisée → Clôture CRM J+1                                   │
│  Appel J+1 — NPS (/10) — Verbatims + feedback                              │
│  Enquête satisfaction — ≤ 72h                                               │
├─────────────────────────────────────────────────────────────────────────────┤
│ P7 / P8 — QUALITÉ & RÉCLAMATIONS                                            │
│  NPS ≤ 5 ? → OUI → P8 — Réclamation (ouverture ≤ 4h ouvrées)             │
│            → NON → Clôturé — Satisfait → Archivage + P7                    │
│  P7 — Rapport qualité mensuel — KPI artisan                                 │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Pipeline CRM — Cycle de vie d'un dossier

```
Appel reçu (CTI — immédiat)
    ↓
En qualification (Script P2 en cours)
    ↓                          ↗ Fiche incomplète (Relance ≤ 30 min)
Fiche complète (Tous champs OK)    ← retour possible
    ↓
    ↗ Urgence détectée → Bascule P5 immédiat
RDV planifié (SMS client ≤ 5 min)
    ↓
En attente artisan (P4 envoyé — relance)
    ↓
Artisan confirmé (Accusé réception)
    ↓
Intervention réalisée (Artisan clôture J+1)
    ↓
    ├── Clôturé Satisfait (NPS ≥ 8 — Archivage)
    ├── Suivi qualité requis (NPS 6–7 — P7)
    └── Réclamation ouverte (NPS ≤ 5 — P8)
          ↓
Dossier clôturé (Archivage définitif + Rapport P7)
```

### SLA & délais cibles

| Priorité | Délai notification artisan |
|----------|---------------------------|
| Urgence | < 5 min |
| Prioritaire | < 30 min |
| Standard | < 1 h |
| Clôture P6 post-intervention | ≤ 72 h |
| Résolution réclamation P8 | ≤ 5 jours |

### KPI cibles

| KPI | Objectif |
|-----|----------|
| Taux de décroché | ≥ 95 % |
| Fiches CRM complètes | 100 % |
| Conversion fiche → RDV | ≥ 80 % |
| Confirmation artisan dans SLA | ≥ 85 % |
| NPS hebdomadaire moyen | ≥ 7,5 / 10 |

---

## Dictionnaire de données

**Résumé : 9 entités — 62+ champs — 34 champs obligatoires**

### 1. Contact (Particulier)

> Fiche client — 1 contact → N affaires

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| Nom / Prénom | Texte | Obligatoire | Non vide |
| Téléphone de rappel | Téléphone | Obligatoire | Format valide |
| Email | Email | Optionnel | Format valide si renseigné |
| Adresse complète d'intervention | Adresse | Obligatoire | Géocodée si possible |
| Code postal / Ville | Texte | Obligatoire | Non vide |
| Canal de contact préférentiel | Liste | Obligatoire | Appel / SMS / Email |
| Historique des affaires | Relation | Automatique | 1 contact → N affaires |

**Obligatoire: 5 | Optionnel: 1 | Automatique: 1**

---

### 2. Artisan

> Profil prestataire — 1 artisan → N affaires

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| Prénom / Nom | Texte | Obligatoire | Non vide |
| Raison sociale | Texte | Obligatoire | Dénomination exacte |
| SIRET | Texte | Obligatoire | 14 chiffres — requis pour facturation |
| Corps de métier(s) | Liste multi | Obligatoire | 12 valeurs possibles |
| Zone géographique | Texte / Géo | Obligatoire | Départements couverts |
| Téléphone mobile (notifications) | Téléphone | Obligatoire | Format valide |
| Email | Email | Obligatoire | Pour souscription et rapports |
| Canaux d'alerte | Liste multi | Obligatoire | SMS / Appel / Email |
| Mode agenda | Liste | Obligatoire | Mode A (structuré) / Mode B (rappel) |
| Plages de disponibilité | Agenda | Optionnel | Requis si Mode A |
| Formule souscrite | Liste | Obligatoire | Selon offres disponibles |
| Date d'activation | Date | Automatique | À la mise en service |
| Statut compte | Liste | Automatique | Actif / Suspendu / Résilié |

**Obligatoire: 9 | Optionnel: 1 | Automatique: 2**

---

### 3. Affaire / Ticket

> Dossier d'intervention — liée à 1 contact + 1 artisan

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| **IDENTIFICATION** | | | |
| Identifiant unique | ID auto | Automatique | Généré à la création |
| Contact lié | Relation | Obligatoire | FK → Contact |
| Artisan lié | Relation | Optionnel | Assigné en P3 |
| **STATUT & PRIORITÉ** | | | |
| Statut du pipeline | Liste | Obligatoire | 14 statuts séquentiels |
| Niveau de priorité | Liste | Obligatoire | Urgence / Prioritaire / Standard |
| Bascule P5 requise | Booléen | Obligatoire | Oui / Non |
| **DATES & TRAÇABILITÉ** | | | |
| Date/heure de création | Timestamp | Automatique | Via CTI ou saisie manuelle |
| Date/heure de qualification | Timestamp | Automatique | Quand tous champs OK |
| Date RDV planifié | Date/Heure | Optionnel | Renseigné en P3 |
| Délai promis au client | Texte | Optionnel | Si mode dégradé |
| Agent qualificateur | User | Automatique | Login utilisateur |
| Source de l'appel | Texte | Automatique | Via CTI / téléphonie |
| Durée de l'appel P2 | Durée | Automatique | En minutes |
| Historique des modifications | Journal | Automatique | Audit trail complet |

**Obligatoire: 4 | Optionnel: 3 | Automatique: 7**

---

### 4. Fiche d'intervention P2

> Qualification détaillée — 1 fiche par affaire

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| **SECTION A — PROBLÈME** | | | |
| Corps de métier | Liste | Obligatoire | 12 métiers disponibles |
| Nature du problème | Liste dyn. | Obligatoire | Arbre de valeurs par métier |
| Description détaillée | Texte libre | Obligatoire | Minimum 30 caractères |
| Localisation précise | Texte | Obligatoire | Cuisine, salle de bain, cave… |
| Ancienneté du problème | Liste | Obligatoire | Quelques h / 1-2 j / Plusieurs j |
| Événement déclencheur | Texte libre | Optionnel | Contexte libre |
| **SECTION B — PRIORITÉ** | | | |
| Niveau de priorité | Liste | Obligatoire | Urgence / Prioritaire / Standard |
| Critère justificatif | Texte court | Obligatoire | Justification de la priorité |
| **SECTION C — ACCÈS & CONTEXTE** | | | |
| Client présent lors intervention | Liste | Obligatoire | Oui / Non / À confirmer |
| Code d'accès / interphone | Texte | Optionnel | Si applicable |
| Contact alternatif | Texte + Tel | Optionnel | Gardien, voisin… |
| Type de logement | Liste | Obligatoire | Maison / Appart. / Commerce / Autre |
| Statut occupant | Liste | Obligatoire | Propriétaire / Bailleur / Locataire |
| Garantie ou contrat entretien | Liste | Obligatoire | Oui / Non / Inconnu |
| Étage / Ascenseur | Texte | Optionnel | Si applicable |
| **SECTION D — MÉDIAS** | | | |
| Photos transmises | Fichiers | Optionnel | Images si disponibles |
| Enregistrement appel | Lien audio | Optionnel | Si système d'enregistrement actif |
| Documents joints | Fichiers | Optionnel | Devis précédents, plans… |

**Obligatoire: 11 | Optionnel: 8**

---

### 5. Rapport de satisfaction P6

> Post-intervention — 1 rapport par affaire clôturée

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| Affaire liée | Relation | Obligatoire | FK → Affaire/Ticket |
| Score NPS | Nombre | Obligatoire | 0 à 10 — déclenche P8 si ≤ 5 |
| Verbatim client | Texte libre | Optionnel | Commentaire libre |
| Statut intervention | Liste | Obligatoire | Réalisée / Partiellement / Non réalisée |
| Date de recueil | Timestamp | Automatique | J+1 après intervention |
| Agent Back-Office | User | Automatique | Login utilisateur |
| Feedback transmis à l'artisan | Booléen | Obligatoire | Oui / Non |

**Obligatoire: 4 | Optionnel: 1 | Automatique: 2**

---

### 6. Activité / Tâche

> Appels, SMS, relances — N activités par affaire

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| Affaire liée | Relation | Obligatoire | FK → Affaire/Ticket |
| Type d'activité | Liste | Obligatoire | Appel / SMS / Email / Tâche / Note |
| Sens | Liste | Obligatoire | Entrant / Sortant |
| Destinataire / Émetteur | Texte | Obligatoire | Client ou Artisan |
| Contenu / Note | Texte libre | Optionnel | Résumé ou message envoyé |
| Date/heure | Timestamp | Automatique | Horodatage systématique |
| Agent responsable | User | Automatique | Login utilisateur |
| Statut tâche | Liste | Optionnel | À faire / En cours / Fait / Annulée |
| Date d'échéance | Date/Heure | Optionnel | Pour les tâches de relance |

**Obligatoire: 4 | Optionnel: 3 | Automatique: 2**

---

## Entités financières

### 7. Devis

> Proposition commerciale artisan → client. Lié à 1 Affaire + 1 Artisan + 1 Contact. Peut évoluer vers un Bon de commande si accepté.

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| **IDENTIFICATION** | | | |
| Numéro de devis | ID auto | Automatique | Format DEV-AAAA-NNNN |
| Affaire liée | Relation | Obligatoire | FK → Affaire/Ticket |
| Artisan émetteur | Relation | Obligatoire | FK → Artisan |
| Client destinataire | Relation | Obligatoire | FK → Contact |
| **CONTENU** | | | |
| Lignes de prestations | Liste (N) | Obligatoire | Libellé + quantité + prix unitaire HT |
| Taux de TVA applicable | Nombre % | Obligatoire | 5,5 % / 10 % / 20 % |
| Total HT | Montant | Automatique | Calculé depuis les lignes |
| Montant TVA | Montant | Automatique | Calculé |
| Total TTC | Montant | Automatique | Calculé |
| Remise éventuelle | % ou € | Optionnel | Appliquée avant TVA |
| Conditions de paiement | Texte | Obligatoire | Acompte / Solde à intervention / 30j |
| Notes et observations | Texte libre | Optionnel | Conditions spécifiques |
| **VALIDITÉ & SUIVI** | | | |
| Date d'émission | Date | Automatique | Jour de création |
| Date de validité | Date | Obligatoire | Par défaut J+30 |
| Statut du devis | Liste | Obligatoire | Brouillon / Envoyé / Accepté / Refusé / Expiré |
| Date d'acceptation / refus | Date | Automatique | Horodatée à la signature |
| Mode d'acceptation | Liste | Optionnel | Signature électronique / Appel / Email |
| Bon de commande lié | Relation | Automatique | Créé automatiquement si accepté |

**Obligatoire: 9 | Optionnel: 3 | Automatique: 7**

---

### 8. Bon de commande

> Validation formelle du devis accepté. Généré depuis un Devis accepté. Déclenche la planification de l'intervention et l'émission ultérieure de la Facture.

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| **IDENTIFICATION** | | | |
| Numéro de BC | ID auto | Automatique | Format BC-AAAA-NNNN |
| Devis d'origine | Relation | Obligatoire | FK → Devis |
| Affaire liée | Relation | Obligatoire | FK → Affaire/Ticket |
| Artisan exécutant | Relation | Obligatoire | FK → Artisan |
| Client | Relation | Obligatoire | FK → Contact |
| **PRESTATIONS CONFIRMÉES** | | | |
| Lignes de prestations | Liste (N) | Automatique | Reprises du devis accepté |
| Montant total TTC | Montant | Automatique | Repris du devis |
| Acompte demandé | Montant / % | Optionnel | Si conditions de paiement le prévoient |
| **PLANIFICATION** | | | |
| Date d'intervention prévue | Date/Heure | Obligatoire | Issue du RDV planifié en P3 |
| Durée estimée | Durée | Optionnel | En heures |
| Instructions spéciales artisan | Texte libre | Optionnel | Accès, outils particuliers… |
| **STATUT** | | | |
| Statut du BC | Liste | Obligatoire | En attente / Confirmé / En cours / Réalisé / Annulé |
| Date de confirmation | Date | Automatique | Quand artisan confirme |
| Facture liée | Relation | Automatique | Créée à la réalisation |

**Obligatoire: 7 | Optionnel: 4 | Automatique: 6**

---

### 9. Facture

> Document légal post-intervention. Émise par l'artisan après intervention réalisée. Liée au Bon de commande et à l'Affaire. Déclenche le suivi de paiement.

| Champ | Type | Statut | Validation / valeurs |
|-------|------|--------|---------------------|
| **IDENTIFICATION LÉGALE** | | | |
| Numéro de facture | ID séquentiel | Obligatoire | Format FAC-AAAA-NNNN — séquence chronologique |
| Bon de commande lié | Relation | Obligatoire | FK → Bon de commande |
| Affaire liée | Relation | Obligatoire | FK → Affaire/Ticket |
| Artisan émetteur (SIRET) | Relation | Obligatoire | FK → Artisan (SIRET obligatoire) |
| Client facturé | Relation | Obligatoire | FK → Contact |
| **DÉTAIL FINANCIER** | | | |
| Lignes de prestations réalisées | Liste (N) | Obligatoire | Libellé + qté + prix unitaire HT |
| Taux de TVA par ligne | Nombre % | Obligatoire | 5,5 % / 10 % / 20 % |
| Total HT | Montant | Automatique | Calculé |
| Montant TVA | Montant | Automatique | Calculé |
| Total TTC | Montant | Automatique | Calculé |
| Acompte déjà versé | Montant | Optionnel | Déduit si applicable |
| Solde restant dû | Montant | Automatique | TTC − acompte versé |
| **PAIEMENT & STATUT** | | | |
| Date d'émission | Date | Automatique | Jour de création |
| Date d'échéance | Date | Obligatoire | Date limite de règlement |
| Mode de paiement | Liste | Obligatoire | Virement / CB / Chèque / Espèces |
| Statut de paiement | Liste | Obligatoire | En attente / Partiel / Payé / En retard / Litigieux |
| Date de paiement effectif | Date | Automatique | À la réception du règlement |
| Avoir associé | Relation | Optionnel | FK → Avoir si remboursement partiel |
| Pénalités de retard | Montant | Automatique | Calculées si dépassement échéance |
| Fichier PDF de la facture | Fichier | Automatique | Généré à l'émission |

**Obligatoire: 12 | Optionnel: 2 | Automatique: 9**

---

## Chaîne documentaire

```
Devis → Bon de commande → Facture
  │           │                │
  │  (accepté)│   (réalisé)    │
  └───────────┘────────────────┘
```

**Points clés :**
- Chaque document est généré automatiquement depuis le précédent
- Le BC se crée à l'acceptation du devis
- La facture se crée à la réalisation de l'intervention
- Le numéro de facture est chronologique obligatoire
- Le SIRET artisan est requis (conformité légale)
- Les pénalités de retard sont calculées automatiquement
- Un champ Avoir associé permet de gérer les remboursements partiels

---

## Workflows appel sortant

### Workflow 1 — Téléprospection artisans

**Objectif :** Convertir un artisan non équipé en souscripteur en un seul appel.

**Plages optimales d'appel :** 7h45–11h30 / 17h–19h

**Tunnel de vente en 6 étapes :**

```
1. ACCROCHE (20 sec)
   → Présentation + raison de l'appel
   → Décroche ? NON → Statut NR — rappel planifié

2. DIAGNOSTIC — Prise de conscience
   → Appels manqués — chiffrage
   → L'artisan reconnaît le problème

3. CONSTAT PARTAGÉ
   → Reformulation + validation accord

4. PRÉSENTATION
   → AlloPro 24/24 — bénéfices
   → Objections :
     • "Pas le temps" → Riposte + retour script (codifié OBJ en CRM)
     • "Déjà une secrétaire" → Riposte + retour script

5. VALEUR ROI
   → ROI chiffré + témoignage

6. CLOSING
   → Question alternative ferme
   → Accord ?
     OUI → Collecte souscription (corps métier + zone + email)
           → Doc souscription envoi ≤ 30 min
           → Statut SOC en CRM
           → Signé ? OUI → Activation compte ≤ 24h
                     NON → Relance J+1 / J+2 / J+3
     NON → Refus — motif noté → Statut HC ou RP en CRM
```

### Statuts CRM prospect artisan

| Code | Signification |
|------|--------------|
| AC | À contacter |
| NR | Non répondu |
| RP | Rappel planifié |
| OBJ | Objection active |
| SOC | Souscrit |
| HC | Hors cible |

---

### Workflow 2 — Contact client suite premier appel

*(Processus de rappel et suivi post-premier contact avec le particulier — intégré dans le flux P3/P4/P6)*

---

## Légende des statuts

| Couleur/Zone | Signification |
|------|--------------|
| Accueil | Téléopérateur — P1 |
| Qualification / Documents | P2 + Devis/BC/Facture |
| RDV / Satisfaction | P3 + P6 |
| Alerte / Acompte | P4 |
| Urgence / Relance | P5 + Relances |
| Qualité / Pilotage | P7 / P8 |

---

*Document extrait du PDF "Synthèse et documentation de projet - Claude.pdf" le 02/06/2026*
