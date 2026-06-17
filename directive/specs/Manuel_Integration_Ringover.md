# Manuel d'intégration Ringover — EspoCRM

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Prérequis](#prérequis)
3. [Configuration côté Ringover](#configuration-côté-ringover)
4. [Configuration côté EspoCRM](#configuration-côté-espocrm)
5. [Choix du fournisseur téléphonique](#choix-du-fournisseur-téléphonique)
6. [Click-to-call](#click-to-call)
7. [Webhooks — Réception des événements d'appels](#webhooks--réception-des-événements-dappels)
8. [Gestion des utilisateurs](#gestion-des-utilisateurs)
9. [Dépannage](#dépannage)
10. [Architecture technique](#architecture-technique)

---

## Vue d'ensemble

L'intégration Ringover permet de :
- Recevoir automatiquement les événements d'appels (entrants/sortants) dans le CRM
- Lancer des appels depuis la fiche prospect (click-to-call)
- Logger automatiquement les appels, durées et enregistrements
- Déclencher les workflows de prospection (changement de statut Lead)
- Cohabiter avec Aircall : chaque équipe ou utilisateur peut utiliser un fournisseur différent

**Compatibilité** : L'intégration Ringover est 100% rétrocompatible. Sans configuration explicite, le système utilise Aircall par défaut. Aucun flux existant n'est affecté.

---

## Prérequis

| Élément | Détail |
|---------|--------|
| Compte Ringover | Plan Business ou Enterprise (accès API requis) |
| Clé API Ringover | Bearer Token depuis le dashboard Ringover |
| Secret Webhook | Clé HMAC-SHA256 pour sécuriser les webhooks |
| Accès Admin EspoCRM | Rôle administrateur pour la configuration |
| URL publique | Le serveur EspoCRM doit être accessible depuis Internet (pour les webhooks) |

---

## Configuration côté Ringover

### Étape 1 — Obtenir la clé API

1. Connectez-vous au [Dashboard Ringover](https://dashboard.ringover.com)
2. Allez dans **Paramètres → API**
3. Générez une nouvelle clé API (Bearer Token)
4. Copiez la clé — elle ne sera plus affichée ensuite

### Étape 2 — Configurer le webhook

1. Dans le Dashboard Ringover, allez dans **Paramètres → Webhooks**
2. Ajoutez un nouveau webhook avec les paramètres suivants :

| Paramètre | Valeur |
|-----------|--------|
| URL | `https://votre-domaine.com/api/v1/ringover-webhook` |
| Format | JSON |
| Secret (HMAC) | Choisissez un secret fort (min. 32 caractères) |
| Événements | `call_ringing`, `call_answered`, `call_ended` |

3. Notez le secret HMAC — vous en aurez besoin dans EspoCRM

### Étape 3 — Identifier les numéros agents

Pour chaque agent/téléprospecteur, notez :
- Son **numéro Ringover** (format E.164, ex: `+33612345678`)
- Ou son **lien direct** Ringover (ex: `https://www.ringover.me/EU_xxxxx`)

---

## Configuration côté EspoCRM

### Accéder au panneau d'administration

1. Connectez-vous en tant qu'administrateur
2. Naviguez vers **Admin → Intégrations** (`/#Admin/integrations`)

### Étape 1 — Enregistrer les identifiants Ringover

Dans la section **"Identifiants Ringover API"** :

1. **API Key (Bearer Token)** : Collez la clé API obtenue depuis le dashboard Ringover
2. **Webhook Secret (HMAC)** : Collez le secret configuré dans le webhook Ringover
3. Cliquez sur **Enregistrer**
4. Cliquez sur **Tester la connexion** pour vérifier que l'API répond

> Le test appelle `GET /v2/team` sur l'API Ringover. Si la connexion est réussie, le nom de l'équipe Ringover s'affiche.

### Étape 2 — Vérifier l'URL webhook

Dans la section **"URLs Webhooks"**, l'URL webhook Ringover est affichée :

```
https://votre-domaine.com/api/v1/ringover-webhook
```

Utilisez le bouton **Copier** pour copier cette URL. Elle doit correspondre exactement à celle configurée dans le dashboard Ringover.

---

## Choix du fournisseur téléphonique

Le système supporte une résolution en cascade pour déterminer quel fournisseur utiliser :

```
Utilisateur → Équipe → Global → Aircall (défaut)
```

### Niveau Global

Dans la section **"Fournisseur de téléphonie"** du panneau admin :

1. Sélectionnez le fournisseur par défaut : **Aircall** ou **Ringover**
2. Cliquez sur **Enregistrer**

Ce choix s'applique à tous les utilisateurs qui n'ont pas de configuration explicite.

### Niveau Équipe

Dans le tableau des équipes :

1. Trouvez l'équipe concernée
2. Sélectionnez le fournisseur : **Aircall**, **Ringover**, ou **"Hérite du global"**
3. La modification est sauvegardée automatiquement

**Cas d'usage** : L'équipe AOPIA utilise Ringover, l'équipe AlloPro utilise Aircall.

### Niveau Utilisateur

La configuration utilisateur est prioritaire sur tout le reste :

1. Admin → Utilisateurs → Éditer l'utilisateur
2. Champ **"Fournisseur téléphonie"** : Aircall, Ringover, ou vide (hérite)

**Cas d'usage** : Un commercial spécifique utilise Ringover alors que son équipe est sur Aircall.

### Tableau récapitulatif de la résolution

| Config User | Config Team | Config Global | Fournisseur utilisé |
|-------------|-------------|---------------|---------------------|
| ringover | — | — | **Ringover** |
| (vide) | ringover | — | **Ringover** |
| (vide) | (vide) | ringover | **Ringover** |
| (vide) | (vide) | (vide) | **Aircall** (défaut) |
| aircall | ringover | ringover | **Aircall** (user prioritaire) |

---

## Click-to-call

### Fonctionnement

Quand un utilisateur clique sur un numéro de téléphone dans le CRM :

1. Le système résout automatiquement son fournisseur (Aircall ou Ringover)
2. L'appel est initié via l'API du fournisseur correspondant
3. Le téléphone de l'agent sonne en premier
4. Une fois décroché, le numéro cible est composé

### Configuration par utilisateur

Dans la section **"Liens directs Ringover par utilisateur"** :

1. Pour chaque utilisateur utilisant Ringover, renseignez son **numéro Ringover** ou son **lien direct** (champ `ringoverUserId` sur le profil)
2. Cliquez sur l'icône **Enregistrer** à côté de chaque utilisateur

> Sans numéro Ringover configuré, l'utilisateur verra le message : "Votre compte n'est pas relié à Ringover. Contactez votre administrateur."

### Format des numéros

Le système normalise automatiquement les numéros en format E.164 :

| Saisie | Normalisé |
|--------|-----------|
| 06 12 34 56 78 | +33612345678 |
| 0612345678 | +33612345678 |
| +33612345678 | +33612345678 |

---

## Webhooks — Réception des événements d'appels

### Événements supportés

| Événement Ringover | Action dans le CRM |
|--------------------|--------------------|
| `call_ringing` | Création de l'entité Call (statut "Planned") |
| `call_answered` | Mise à jour du Call (statut "Held") |
| `call_ended` | Finalisation : durée, enregistrement, pipeline |

### Traitement des appels entrants

Quand un appel entrant est reçu :
1. Le numéro appelant est recherché dans les Leads/Accounts/Contacts
2. Le Call est créé et lié à l'entité correspondante
3. Si c'est un Lead en prospection, le statut passe automatiquement à "STD-Joint"

### Traitement des appels sortants

Quand un appel sortant se termine :
1. Le Call est loggé avec durée et enregistrement
2. Si l'appel est répondu (durée > 0), le pipeline du Lead est mis à jour
3. Si l'appel est manqué (durée = 0), le statut Lead peut passer à "STD-NR"

### Sécurité — Validation HMAC

Chaque webhook est validé par signature HMAC-SHA256 :
- Le corps de la requête est signé avec le secret webhook
- La signature est envoyée dans l'en-tête `X-Ringover-Signature`
- Si la signature est invalide, l'événement est ignoré (mais retourne HTTP 200)

### Idempotence

Le champ `ringoverCallId` sur l'entité Call garantit qu'un même appel n'est pas loggé deux fois, même si Ringover envoie le webhook plusieurs fois.

---

## Gestion des utilisateurs

### Ajouter un nouvel agent Ringover

1. **Admin → Utilisateurs** → Éditer l'utilisateur
2. Renseigner le champ **ringoverUserId** (numéro Ringover ou identifiant interne)
3. Optionnel : forcer **telephonyProvider = "ringover"** sur le profil
4. Dans le panneau admin Intégrations, ajouter le **lien direct Ringover** si applicable

### Migrer un utilisateur d'Aircall vers Ringover

1. Modifier le champ **telephonyProvider** de l'utilisateur → "ringover"
2. Renseigner son **ringoverUserId**
3. L'ancien **aircallUserId** peut rester (pas de conflit)
4. Les futurs appels utiliseront Ringover ; l'historique Aircall reste intact

### Migrer une équipe entière

1. Admin → Intégrations → Tableau des équipes
2. Changer le fournisseur de l'équipe → "Ringover"
3. Configurer le ringoverUserId pour chaque membre de l'équipe
4. Tous les utilisateurs de l'équipe (sans config user explicite) passeront sur Ringover

---

## Dépannage

### Le click-to-call ne fonctionne pas

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| "Votre compte n'est pas relié à Ringover" | Pas de `ringoverUserId` | Configurer le numéro Ringover dans le profil |
| "Erreur de connexion Ringover" | Timeout API | Vérifier que le serveur a accès à `public-api.ringover.com` |
| "Clé API Ringover invalide" | Token expiré/incorrect | Régénérer la clé dans le dashboard Ringover |
| Rien ne se passe | Fournisseur = Aircall | Vérifier la résolution : User → Team → Global |

### Les webhooks ne sont pas reçus

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| Aucun appel loggé | URL webhook incorrecte | Vérifier l'URL dans le dashboard Ringover |
| Appels loggés mais ignorés | Signature HMAC invalide | Vérifier que le secret est identique des deux côtés |
| Erreurs 502/503 | Serveur EspoCRM down | Vérifier que le serveur est accessible depuis Internet |
| Doublons d'appels | Ringover re-essaie | Normal — l'idempotence via `ringoverCallId` empêche les doublons |

### Vérifier les logs

Les logs Ringover sont dans les logs EspoCRM :

```bash
docker exec espocrm-app tail -f /var/www/html/data/logs/espo.log | grep -i ringover
```

Messages typiques :
- `Ringover webhook: invalid HMAC signature` → Secret incorrect
- `Ringover webhook: error processing 'call_ended'` → Erreur de traitement
- `Ringover: could not log webhook event` → Erreur non bloquante

### Tester la connexion API manuellement

```bash
curl -H "Authorization: Bearer VOTRE_CLE_API" \
     https://public-api.ringover.com/v2/team
```

Réponse attendue : JSON avec les informations de l'équipe.

---

## Architecture technique

### Flux de données

```
┌─────────────┐     Webhook      ┌──────────────────┐
│  Ringover   │ ───────────────→ │  /ringover-webhook │
│  Cloud API  │                  │  (Controller)      │
└─────────────┘                  └────────┬───────────┘
                                          │
                                          ▼
                                 ┌────────────────────┐
                                 │ RingoverWebhook     │
                                 │ Normalizer          │
                                 │ (Ringover → interne)│
                                 └────────┬───────────┘
                                          │
                              ┌───────────┴───────────┐
                              │                       │
                              ▼                       ▼
                   ┌───────────────────┐   ┌──────────────────────┐
                   │ InboundCallHandler │   │ AircallCallLogging   │
                   │ (appels entrants)  │   │ Service (sortants)   │
                   └───────────────────┘   └──────────────────────┘
                              │                       │
                              ▼                       ▼
                   ┌───────────────────────────────────────────┐
                   │          Entité Call (EspoCRM)             │
                   │  + ringoverCallId (idempotence)            │
                   │  + lien vers Lead/Account/Contact          │
                   └───────────────────────────────────────────┘
```

### Flux click-to-call

```
┌──────────┐  Click numéro  ┌─────────────────────┐  Resolve provider  ┌──────────────────────┐
│ Frontend │ ──────────────→ │ TelephonyDial       │ ─────────────────→ │ TelephonyProvider    │
│ (CRM UI) │                 │ (EntryPoint)        │                    │ Resolver             │
└──────────┘                 └─────────┬───────────┘                    └──────────────────────┘
                                       │
                            ┌──────────┴──────────┐
                            │                     │
                            ▼                     ▼
                 ┌────────────────┐    ┌────────────────────┐
                 │ AircallDial    │    │ RingoverProvider    │
                 │ (EntryPoint)   │    │ ::dial()            │
                 └────────────────┘    └────────────────────┘
                            │                     │
                            ▼                     ▼
                 ┌────────────────┐    ┌────────────────────┐
                 │ Aircall API    │    │ Ringover Callback   │
                 │ (click2call)   │    │ API (/v2/callbacks) │
                 └────────────────┘    └────────────────────┘
```

### Fichiers principaux

| Fichier | Rôle |
|---------|------|
| `custom/Espo/Custom/Controllers/Ringover.php` | Controller webhook + dial |
| `custom/Espo/Custom/Services/RingoverProvider.php` | Client API Ringover |
| `custom/Espo/Custom/Services/RingoverWebhookNormalizer.php` | Normalisation événements |
| `custom/Espo/Custom/Services/TelephonyProviderResolver.php` | Résolution fournisseur |
| `custom/Espo/Custom/EntryPoints/TelephonyDial.php` | Click-to-call unifié |
| `custom/Espo/Custom/Resources/routes.json` | Routes API (webhook, dial) |
| `client/custom/res/templates/admin/integrations/panel.tpl` | Interface admin |
| `client/custom/src/views/admin/integrations/panel.js` | Logique JS admin |

### Variables d'environnement (.env)

```env
RINGOVER_API_KEY=votre_bearer_token_ici
RINGOVER_WEBHOOK_SECRET=votre_secret_hmac_ici
```

---

## Checklist de mise en production

- [ ] Clé API Ringover générée et testée
- [ ] Secret webhook configuré des deux côtés (Ringover + EspoCRM)
- [ ] URL webhook accessible depuis Internet (HTTPS obligatoire)
- [ ] Test de connexion réussi depuis le panneau admin
- [ ] Fournisseur global/team/user configuré selon les besoins
- [ ] `ringoverUserId` renseigné pour chaque agent concerné
- [ ] Test click-to-call fonctionnel pour au moins un agent
- [ ] Vérification qu'un appel test génère bien une entité Call
- [ ] Vérification que le pipeline Lead est déclenché correctement
- [ ] Vérification que les anciens flux Aircall fonctionnent toujours
