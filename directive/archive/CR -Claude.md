**COMPTE RENDU DE RÉUNION**

Projet CRM - MBL

_Date : \[à compléter\] | Durée : ~1h15_

# **1\. Participants**

- Alexandre - Direction commerciale (Poitiers)
- Ranto - Responsable opérations MBL (équipes sports, TV, téléprospection)
- Bruno - Accompagnement CRM / terrain
- Franck - Équipe ADV / clients
- Rent - Responsable équipes phoning partenaires
- Rizzo (Erizo) - Développeur CRM (Dolibarr)

# **2\. Contexte et objectifs**

L'objectif de cette réunion était de faire le point sur l'avancement du CRM commercial en cours de développement sur Dolibarr (ERP open source). Le projet couvre deux périmètres distincts :

- CRM Partenaires : gestion des CSE prospects et partenaires (suivi des campagnes de téléprospection, statuts, commerciaux assignés)
- CRM Clients : gestion des stagiaires / bénéficiaires de formations (référence client, produits, parcours multi-modules)

# **3\. CRM Partenaires - Points discutés**

## **3.1 Structure du CRM partenaire**

Rizzo a présenté l'avancement du CRM partenaire. La structure comprend trois objets principaux :

- Partenaire (prospect/CSE)
- Contact (interlocuteurs au sein du CSE : secrétaire, référent…)
- Opportunité (lien entre le prospect et le commercial)

Des clarifications ont été demandées sur le rôle de chaque objet et les transitions entre statuts.

## **3.2 Statuts - simplification demandée**

Plusieurs statuts ont été jugés redondants ou peu clairs. Les points soulevés :

- "Standard joint" : utilité peu claire, à revoir
- "Rendez-vous planifié" apparaissait deux fois - confusion avec "Rappel planifié"
- "Rendez-vous qualifié" : terme à remplacer par "En attente suite à rendez-vous" ou "En discussion"
- Statuts proposés après RDV : En cours de négociation / Refus / Signé

Décision : Alexandre et Bruno retravailler les statuts à froid. Les statuts doivent être libres (non séquentiels) pour permettre de sauter des étapes et de revenir en arrière (ex : refus → relance 6 mois plus tard).

## **3.3 Affectation des fiches et flux de production**

Points clés discutés :

- Une fiche peut être affectée manuellement ou automatiquement à une téléprospectrice
- Si le standard est joint mais pas le bon interlocuteur → la fiche reste sur la télépro ou bascule selon les règles de gestion
- Répartition aléatoire des appels : à définir précisément (vrai aléatoire ou rotation équitable entre télépros)
- Objectif : éviter qu'une seule téléprospectrice appelle toujours pour le même commercial
- Le "cycle de vie de la fiche" (depuis l'arrivée sur l'écran de la télépro jusqu'à la signature ou refus) est le point le plus critique à modéliser

## **3.4 Commercial précédent**

Ajout demandé d'un champ "Commercial précédent" sur la fiche partenaire, afin de tracer l'historique d'attribution (ex : CSE géré par Élisabeth Salgueiro, puis transféré à Philippe Hubert).

## **3.5 Liste des commerciaux**

La liste déroulante des commerciaux doit être triée par ordre alphabétique du nom de famille. Quelques noms obsolètes sont à supprimer (ex : Élisabeth Salgueiro).

## **3.6 Droits et permissions**

Proposition de restreindre certains statuts selon les profils :

- Statut "Convention signée" : uniquement Alexandre et Bruno (pas les commerciaux ni les télépros)
- Statut "Rendez-vous qualifié" : accessible aux commerciaux
- Les télépros et l'ADV voient tous les statuts pertinents à leur activité

# **4\. CRM Clients - Points discutés**

## **4.1 Référence client vs référence produit**

Distinction importante soulevée : la "référence client" actuelle est en réalité une référence produit (proposition commerciale). Proposition d'adopter une vraie référence client unique (numéro auto ou basé sur l'année) pour éviter les doublons.

Un client peut suivre 2 à 3 formations (parcours multi-modules). Il faut qu'il n'existe qu'une seule fiche client, avec plusieurs produits/formations associés.

- Exemple : client avec Excel Expert (2020) + Photoshop Niveau 1 (2026) → 1 fiche client, 2 références produit
- Identifiant technique unique ("ID technique") déjà présent dans le CRM existant : à exploiter

## **4.2 Champs à épurer**

Franck et Alexandre prendront en charge la revue des champs du CRM client avec Rizzo. Certains champs exportés (heures réalisées, heures restantes…) ne sont pas nécessaires côté CRM commercial et seront supprimés.

## **4.3 Gestion des doublons clients**

Des homonymes peuvent exister. La date de naissance sera utilisée comme critère de différenciation supplémentaire.

# **5\. Organisation et prochaines étapes**

## **5.1 Répartition des équipes**

Pour avancer plus efficacement, deux sous-groupes ont été définis :

- Groupe Partenaires : Rent + Bruno + Rizzo → focus sur le CRM partenaire, les statuts et le flux téléprospection
- Groupe Clients : Alexandre + Franck + Rizzo → focus sur le CRM client, les champs, les références produit/client

## **5.2 Accès et outillage**

- Rizzo enverra les accès au CRM d'ici mercredi pour que Bruno, Franck et Alexandre puissent travailler dessus
- Un cahier des charges sera rédigé par le groupe clients et transmis à Rizzo pour développement
- L'outil de phoning utilisé est une plateforme open source (à préciser par mail)
- Dolibarr est rappelé comme un ERP open source, pas un CRM natif - certaines limitations en découlent

## **5.3 Formations utilisateurs**

Une formation à l'outil sera nécessaire pour les équipes ADV et commerciaux. Les équipes ADV étant habituées aux CRM, l'onboarding sera facilité.

# **6\. Plan d'actions**

| **Action**                                                                                 | **Responsable**      | **Échéance**       |
| ------------------------------------------------------------------------------------------ | -------------------- | ------------------ |
| Retravailler et simplifier les statuts du CRM partenaire                                   | Alexandre + Bruno    | Mercredi           |
| Envoyer les accès CRM à Alexandre, Franck, Bruno                                           | Rizzo                | Mercredi           |
| Rédiger le cahier des charges CRM client (champs, référence client/produit)                | Franck + Alexandre   | Semaine S          |
| Définir le flux complet "cycle de vie d'une fiche" téléprospection                         | Rent + Bruno + Rizzo | Point demain matin |
| Clarifier les règles de répartition aléatoire des appels entre télépros                    | Alexandre + Rizzo    | À planifier        |
| Ajouter champ "Commercial précédent" dans la fiche partenaire                              | Rizzo                | Semaine S          |
| Trier la liste des commerciaux par ordre alphabétique (nom de famille) et la mettre à jour | Bruno                | Mercredi           |
| Envoyer le nom et lien de l'outil de phoning par mail                                      | Rizzo                | Ce soir            |
| Viser opérationnalité CRM pour commerciaux et ADV                                          | Rizzo + équipe       | Semaine prochaine  |

_Document généré automatiquement - à valider par les participants_