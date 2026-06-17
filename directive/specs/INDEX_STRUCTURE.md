Index et structure du projet CRM AOPIA / LIKE Formation
=======================================================

Résumé rapide
-------------

Ce dépôt contient le CRM commercial NS Conseil (Laravel + Filament) et la documentation CDC issue du cahier des charges EspoCRM initial.

**Document principal de modélisation** : [`Modele_Projet_AOPIA_Laravel.md`](Modele_Projet_AOPIA_Laravel.md)

| Document | Contenu |
|---|---|
| `Modele_Projet_AOPIA_Laravel.md` | Modèle de données, workflows, mapping Excel, écarts CDC |
| `CDC_CRM_EspoCRM_AOPIA (2).md` | Cahier des charges fonctionnel complet |
| `Champs_Requis_Par_Entite.md` | Champs obligatoires par entité |
| `Manuel_Integration_Ringover.md` | Intégration téléphonie |
| `split-account-table.md` | Découpage fiche partenaire (satellites) |
| `directive/new directive aopiacrm/` | Workflows HTML v2 (prospection + statuts) |
| `directive/archive/` | Fichiers Excel réels + CR réunions |

---

Index EspoCRM (référence historique)
-----------------------------------

Ce document donne un index concis des dossiers principaux du projet EspoCRM d'origine et la logique d'organisation.

Structure principale
-------------------

- Racine: fichiers de configuration, scripts, Docker et outils de build.
- `application/`: cœur PHP de l'application (namespace `Espo`). Contrôleurs, modèles, services.
- `client/`: sources et assets front (css, `src/`, images, fonts, modules JS).
- `custom/`: personnalisations et extensions (`custom/Espo/`) — éviter de modifier le core.
- `data/`: configurations runtime (`config.php`, `config-db.php`), `cache/`, `logs/`, `upload/`, `tmp/`.
- `dev/`: scripts et utilitaires de développement, seeds et helpers (PHPStan, debug helpers).
- `frontend/`: configuration et outils de build frontend (`less/`, `bundle-config.json`).
- `public/`: point d'entrée web public (index.php, `api/`, `portal/`, `install/`).
- `build/` / `upgrades/`: artefacts de build et scripts de migration.
- `scripts/`: scripts d'import et utilitaires (imports Excel, etc.).
- `templates/`: gabarits (emails, etc.).
- `tests/`, `playwright-tests/`: suites de tests unitaires et E2E.
- `vendor/`: dépendances gérées par Composer.

Fichiers clés à la racine
-------------------------

- `composer.json` / `composer.lock` — dépendances PHP.
- `package.json`, `Gruntfile.js`, `tsconfig.json`, `jsconfig.json` — tooling frontend.
- `README.md`, `INSTALL_STEPS.md` — documentation d'installation.
- `Dockerfile`, `docker-compose.yml` — configuration conteneurisée.

Logique d'organisation
----------------------

- Séparation clair backend/frontend: backend dans `application/`, frontend dans `client/` + `frontend/`, `public/` expose l'application.
- Personnalisation via `custom/` pour éviter les modifications du core et faciliter les mises à jour.
- `data/` contient l'état et la configuration du site — conserver ces fichiers hors du contrôle de version sensible (ex: backups, `.env`).
- `vendor/` ne doit pas être modifié manuellement — gérer via Composer.
- Les scripts d'upgrade et `upgrades/` gèrent les migrations de schéma et opérations post-update.

Où ajouter une customisation
---------------------------

- Pour ajouter/override une entité, vue ou logique PHP: créer `custom/Espo/<Module>/...` suivant l'arborescence du core.
- Pour ajouter des assets front: travailler dans `client/custom/` ou ajouter des modules dans `client/src/` puis rebuild.

Conseils rapides
----------------

- Avant mise en production, vérifier `data/config-*.php` et sécuriser `public/`.
- Utiliser Composer et npm/Grunt pour gérer dépendances et builds.
- Lancer les tests (PHPUnit, Playwright) après modifications critiques.

Exemples de prochaines étapes
----------------------------

- Documenter l'emplacement exact pour une surcharge type (ex: `custom/Espo/Resources/metadata/entity.json`).
- Générer un `tree` détaillé pour indexation automatique si nécessaire.

Fin
