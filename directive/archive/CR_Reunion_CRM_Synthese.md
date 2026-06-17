# Compte-Rendu de Réunion — CRM EspoCRM (Synthèse)

**Projet :** CRM Commercial AOPIA / LIKE Formation (NS Conseil)
**Outil :** EspoCRM (open source, self-hosted)
**Durée :** ~1h15

---

## Participants

| Nom | Rôle |
|---|---|
| Alexandre FLOREK | Direction commerciale (Poitiers) |
| Bruno BIARDOUX | Responsable partenariats / terrain |
| Franck PINO CORTES | Responsable ADV / RC |
| Ranto | Responsable opérations MBL (équipes téléprospection) |
| Erizo | Développeur CRM |

---

## 1. Objectif de la réunion

Point d'avancement sur le CRM commercial en cours de développement. Deux périmètres :
- **CRM Partenaires** — prospection CSE / syndicats / entreprises
- **CRM Clients** — bénéficiaires de formation (import Dolibarr)

---

## 2. CRM Partenaires — Décisions

### Structure
Trois objets : **Partenaire** (prospect/CSE signé), **Contact** (interlocuteurs CSE), **Opportunité** (signal faible, sas d'entrée).

### Statuts du pipeline — À simplifier

| Problème identifié | Décision |
|---|---|
| Trop de statuts, confusion entre termes | Retravailler à froid (Bruno + Ranto) |
| "RDV planifié" vs "RDV qualifié" | Remplacer "qualifié" par "En attente suite à RDV" ou "En discussion" |
| Séquence obligatoire | **Non** — statuts libres, possibilité de sauter et revenir en arrière |
| Après RDV | 3 issues : En discussion / Refus / Convention signée |

### Droits et permissions

| Action | Qui peut |
|---|---|
| Passer en "Convention signée" | **Uniquement** Alexandre et Bruno |
| Mettre "RDV qualifié" / "En discussion" | Commerciaux |
| Voir tous les statuts | Équipes ADV + Team Leader |
| S'attribuer un prospect | Commerciaux (si en cours de prospection) |
| Convertir prospect → partenaire | Uniquement Bruno / Alexandre |

### Visibilité par profil

| Profil | Voit |
|---|---|
| Téléprospecteur | AC → KO (son pipeline phoning, ses fiches) |
| Commercial | En cours de prospection → RDV → En discussion |
| ADV (Niort) | Tout, y compris RDV planifié |
| Team Leader | Toute la base, tous statuts |

### Flux "cycle de vie d'une fiche"

1. Fiche importée → affectée à un téléprospecteur
2. Appel passé → statut mis à jour + commentaires
3. Si rappel planifié → fiche revient automatiquement à la date/heure
4. Si RDV obtenu → bascule vers le commercial assigné
5. Si refus → archivage, mais réactivation possible (retour AC)

**Point critique :** définir comment la fiche est réaffectée après chaque action.

### Répartition des appels

- Rotation équitable entre téléprospecteurs (pas de vrai aléatoire)
- Répartition par département tournante (ex: 3 télépros × 3 départements)
- Éviter qu'une seule personne appelle toujours pour le même commercial

### Ajouts demandés sur Partenaire

- Champ **"Commercial précédent"** (historique d'attribution)
- **Adresse lieu RDV** structurée : rue / code postal / ville
- **Liste commerciaux** triée par nom de famille (alphabétique), noms obsolètes à supprimer
- Bloc Dirigeant : optionnel, peut être masqué

---

## 3. CRM Clients — Décisions

| Sujet | Décision |
|---|---|
| Référence client | Utiliser l'**ID technique** Dolibarr comme identifiant unique |
| "Réf. client" actuelle | C'est une référence **produit/formation**, pas client |
| Multi-formations | 1 fiche client → plusieurs produits (parcours P2 = min. 2 formations) |
| Déduplication | Par nom + prénom + date de naissance |
| Heures de formation | Pas nécessaire pour les commerciaux — à retirer |
| Champs | Franck + Alexandre feront le tri utile/inutile |

---

## 4. Opportunités

- Sert de "sas d'entrée" quand un client mentionne un CSE potentiel
- Exemple : client dit "ma femme travaille dans tel CSE" → opportunité créée
- L'ADV et les commerciaux peuvent créer et s'attribuer des opportunités
- Statuts : Nouveau → Qualifié → Converti en Prospect / Perdu

---

## 5. Organisation du travail

| Groupe | Membres | Périmètre |
|---|---|---|
| **Partenaires** | Bruno + Ranto + Erizo | Pipeline, statuts, flux phoning, commerciaux |
| **Clients** | Alexandre + Franck + Erizo | Champs, références, produits, import |

---

## 6. Plan d'actions

| # | Action | Responsable | Échéance |
|---|---|---|---|
| 1 | Simplifier les statuts du pipeline prospect | Bruno + Ranto | Cette semaine |
| 2 | Définir les règles de répartition des fiches (rotation) | Bruno + Ranto + Erizo | Cette semaine |
| 3 | Fournir la liste des commerciaux à jour | Bruno | Mercredi |
| 4 | Nettoyer les champs du module Client | Franck + Alexandre | Cette semaine |
| 5 | Donner les accès CRM | Erizo | Mercredi |
| 6 | Documenter le flux "vie d'une fiche" prospect | Erizo | Envoi + point Bruno |
| 7 | Ajouter champ "commercial précédent" sur Partenaire | Erizo | Cette semaine |
| 8 | Structurer l'adresse lieu RDV (rue/CP/ville) | Erizo | Cette semaine |
| 9 | Rédiger cahier des charges CRM Client | Franck + Alexandre | Semaine S |
| 10 | Envoyer nom + lien outil phoning par mail | Erizo | Ce soir |

---

## 7. Points en suspens

- Enregistrement et stockage des conversations téléphoniques
- Intégration plateforme d'appel (Aircall) ↔ EspoCRM
- Feuille de route commerciale hebdomadaire (extraction auto du CRM)
- Formation des équipes (ADV habituées aux CRM, onboarding facilité)
- Coût : open source, pas d'abonnement, hébergement à prévoir

---

## 8. Prochaines étapes

1. **Mercredi** — Accès CRM pour Bruno/Franck + point partenaires (après-midi)
2. **Fin de semaine** — CRM opérationnel pour commerciaux et ADV
3. **Semaine prochaine** — Début du phoning sur le CRM (équipes Ranto)

---

*Synthèse consolidée à partir de deux comptes-rendus de la même réunion.*
