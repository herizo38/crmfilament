# Compte-Rendu de Réunion — CRM EspoCRM AOPIA / LIKE Formation

**Date :** Mai 2026
**Participants :**
- Alexandre FLOREK (Co-Gérant, AOPIA/LIKE)
- Bruno BIARDOUX (Responsable partenariats)
- Franck PINO CORTES (Responsable ADV/RC)
- Ranto (Responsable opérations MBL — équipes téléprospection)
- Erizo (Développeur CRM)

**Objet :** Point d'avancement CRM EspoCRM — Modules Partenaires, Prospects et Clients

---

## 1. Contexte

Réunion plénière pour valider l'avancement du CRM EspoCRM. Deux modules principaux à traiter :
- **CRM Partenaires** (prospection CSE/syndicats/entreprises)
- **CRM Clients** (bénéficiaires de formation)

---

## 2. Décisions prises

### 2.1 Organisation des équipes

| Responsable | Périmètre |
|---|---|
| **Bruno + Ranto** | Module Partenaires (prospection, phoning, commerciaux) |
| **Alexandre + Franck** | Module Clients (bénéficiaires, formations, produits) |
| **Erizo** | Développement technique CRM |

### 2.2 Statuts du pipeline prospects — À revoir

- Trop de statuts actuellement, source de confusion
- Les termes "RDV planifié" et "RDV qualifié" prêtent à confusion
- **Décision :** Bruno et Ranto reprennent les statuts à froid pour simplifier
- **Principe retenu :** Les statuts doivent être **libres** (pas de séquence obligatoire) — on doit pouvoir sauter d'un statut à un autre et revenir en arrière
- Après "RDV planifié", proposer : "En attente suite à RDV" ou "En discussion" au lieu de "Qualifié"

### 2.3 Droits et conversion

- **Seuls Bruno et Alexandre** peuvent passer un prospect en "Convention signée"
- Aucun commercial ni téléprospecteur ne peut convertir
- Un commercial peut mettre "RDV qualifié" mais pas "Signé"
- Les statuts doivent être indépendants selon qui gère (téléprospecteur vs commercial vs ADV)

### 2.4 Visibilité des statuts par profil

- **Téléprospecteurs :** voient AC → KO (leur pipeline phoning)
- **Commerciaux :** voient "En cours de prospection" et au-delà (RDV planifié, etc.)
- **Équipes ADV (filles de Niort) :** doivent tout voir, y compris RDV planifié
- Un commercial doit pouvoir **s'attribuer** un prospect en cours de prospection

### 2.5 Flux de la fiche prospect (cycle de vie)

- La fiche arrive sur l'écran du téléprospecteur
- Après appel : mise à jour du statut + commentaires
- Si rappel planifié : la fiche revient automatiquement à la date/heure prévue
- Si RDV obtenu : bascule vers le commercial
- Si refus : possibilité de réactiver plus tard (retour en AC)
- **Point clé :** définir comment la fiche est réaffectée après chaque action

### 2.6 Répartition des appels (aléatoire)

- Alexandre veut une rotation équitable entre téléprospecteurs
- Pas de "vrai aléatoire" — plutôt une répartition par département tournante
- Exemple : 3 téléprospecteurs × 3 départements en rotation
- **À définir :** règles précises de répartition (point Bruno + Ranto)

### 2.7 Module Clients — Points importants

| Sujet | Décision |
|---|---|
| Référence client | Utiliser l'**ID technique** existant dans Dolibarr comme identifiant unique |
| Référence produit | La "réf. client" actuelle est en fait une référence produit/formation |
| Multi-formations | Un client peut avoir 2-3 formations (parcours P2) → une seule fiche client, plusieurs produits |
| Déduplication | Par date de naissance en cas de doublons nom/prénom |
| Heures de formation | Pas nécessaire pour les commerciaux — à retirer du CRM commercial |
| Champs à nettoyer | Franck et Alexandre feront le tri des champs utiles vs inutiles |

### 2.8 Partenaires — Ajouts demandés

- **Commercial précédent** : ajouter un champ pour garder l'historique (antériorité)
- **Bloc Dirigeant** : peut être retiré si non nécessaire
- **Liste des commerciaux** : trier par nom de famille (ordre alphabétique)
- **Adresse du lieu RDV** : structurer en rue / code postal / ville (pas de saisie libre)

### 2.9 Opportunités

- Sert de "sas d'entrée" quand un client mentionne un CSE potentiel
- Exemple : un client dit "ma femme travaille dans tel CSE" → créer une opportunité
- L'équipe ADV doit pouvoir créer et s'attribuer des opportunités

---

## 3. Actions à mener

| # | Action | Responsable | Échéance |
|---|---|---|---|
| 1 | Revoir et simplifier les statuts du pipeline | Bruno + Ranto | Cette semaine |
| 2 | Définir les règles de répartition des fiches (rotation téléprospecteurs) | Bruno + Ranto + Erizo | Cette semaine |
| 3 | Fournir la liste des commerciaux à jour (ordre alphabétique nom) | Bruno | Mercredi |
| 4 | Nettoyer les champs du module Client (garder/supprimer) | Franck + Alexandre | Cette semaine |
| 5 | Donner les accès CRM à Bruno et Franck | Erizo | Mercredi |
| 6 | Documenter le flux "vie d'une fiche" (cycle de vie prospect) | Erizo | Envoi puis point avec Bruno |
| 7 | Ajouter le champ "commercial précédent" sur Partenaire | Erizo | Cette semaine |
| 8 | Structurer l'adresse du lieu RDV (rue/CP/ville) | Erizo | Cette semaine |
| 9 | Point CRM Partenaires | Bruno + Ranto + Erizo | Mercredi après-midi |
| 10 | Point CRM Clients | Franck + Alexandre | À planifier |

---

## 4. Points en suspens

- Enregistrement des conversations téléphoniques (stockage dans le CRM ?)
- Intégration plateforme d'appel (Aircall) avec EspoCRM
- Feuille de route commerciale hebdomadaire (extraction automatique du CRM à terme)
- Formation des équipes à l'utilisation du CRM
- Coût de la solution (hébergement, pas d'abonnement — open source)

---

## 5. Prochaines étapes

1. **Mercredi** : accès CRM pour Bruno et Franck + point partenaires
2. **Fin de semaine** : CRM opérationnel pour les commerciaux et l'ADV (objectif)
3. **Semaine prochaine** : début du phoning sur le CRM (équipes de Ranto)

---

*CR rédigé à partir de la transcription de la réunion.*
