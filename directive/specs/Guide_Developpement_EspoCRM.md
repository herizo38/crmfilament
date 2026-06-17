# Guide de Développement EspoCRM - NS Conseil

## Architecture du projet

```
custom/Espo/Custom/
├── Controllers/        → API endpoints (backend)
├── Services/           → Logique métier
├── Hooks/              → Événements avant/après CRUD
├── Entities/           → Modèles de données PHP
├── Jobs/               → Tâches planifiées (cron)
├── EntryPoints/        → Points d'entrée HTTP spéciaux
├── Select/             → Filtres de requêtes personnalisés
├── Acl/                → Règles d'accès custom
├── Utils/              → Classes utilitaires
├── Resources/
│   ├── metadata/
│   │   ├── entityDefs/     → Définition des champs et relations
│   │   ├── clientDefs/     → Configuration interface (vues, boutons)
│   │   ├── scopes/         → Déclaration des entités
│   │   ├── recordDefs/     → Actions sur les enregistrements
│   │   ├── selectDefs/     → Filtres de sélection
│   │   ├── layouts/        → Layouts (list, detail, etc.)
│   │   ├── aclDefs/        → Permissions
│   │   └── scheduledJobs/  → Jobs planifiés
│   ├── i18n/               → Traductions (fr_FR, en_US)
│   ├── layouts/            → Layouts JSON
│   ├── routes.json         → Routes API custom
│   └── module.json         → Déclaration du module
│
client/custom/
├── src/
│   ├── controllers/    → Contrôleurs frontend (navigation)
│   └── views/          → Vues JavaScript (UI)
├── res/templates/      → Templates HTML (Handlebars)
└── modules/            → Modules frontend additionnels
```

## Structure des dossiers principaux

- `application/`: cœur PHP d'EspoCRM, code serveur, contrôleurs, entités, services, repos.
- `client/`: sources frontend de l'application, assets JS/CSS, vues et router.
- `custom/`: surcharges et extensions personnalisées. Toujours privilégier ce dossier pour éviter de modifier le core.
- `data/`: configuration runtime (`config.php`, `config-db.php`), cache, logs, uploads, fichiers d'état.
- `dev/`: scripts de développement et tests utilitaires.
- `frontend/`: configuration et build des assets frontend.
- `public/`: point d'entrée web public, API publiques, portail et installation.
- `build/`, `upgrades/`: artefacts de build et scripts de migration.
- `scripts/`: scripts d'import et utilitaires.
- `templates/`: gabarits HTML / e-mails.
- `tests/`, `playwright-tests/`: tests unitaires et E2E.
- `vendor/`: dépendances Composer.

## Où ajouter une customisation ?

- Logique PHP, contrôleurs, services, hooks, entités : `custom/Espo/Custom/`
- Metadata et interface : `custom/Espo/Custom/Resources/metadata/`
- Traductions : `custom/Espo/Custom/Resources/i18n/`
- Routes API custom : `custom/Espo/Custom/Resources/routes.json`
- Vues frontend personnalisées : `client/custom/src/views/` et `client/custom/src/controllers/`
- Templates frontend personnalisées : `client/custom/res/templates/`

## Modifier le code par défaut (Lead, Account, Call, Calendar, ...)

### 1) Où se trouve le code par défaut

Le code standard est dans le module CRM :

- `application/Espo/Modules/Crm/Entities/` — entités PHP (`Lead.php`, `Account.php`, `Call.php`, `Meeting.php`, etc.)
- `application/Espo/Modules/Crm/Controllers/` — contrôleurs des actions côté backend
- `application/Espo/Modules/Crm/Resources/metadata/` — metadata de l'interface et des entités
- `application/Espo/Modules/Crm/Resources/routes.json` — routes API et actions custom
- `application/Espo/Modules/Crm/Resources/layouts/` — layouts de vues, calendriers, listes

### 2) Bonne pratique : override dans `custom/`

Ne modifiez pas directement `application/Espo/Modules/Crm/` si possible. Créez plutôt les mêmes fichiers dans :

- `custom/Espo/Modules/Crm/Entities/Lead.php`
- `custom/Espo/Modules/Crm/Entities/Account.php`
- `custom/Espo/Modules/Crm/Entities/Call.php`
- `custom/Espo/Modules/Crm/Controllers/Lead.php`
- `custom/Espo/Modules/Crm/Controllers/Account.php`
- `custom/Espo/Modules/Crm/Controllers/Call.php`
- `custom/Espo/Modules/Crm/Resources/metadata/entityDefs/Lead.json`
- `custom/Espo/Modules/Crm/Resources/metadata/clientDefs/Account.json`
- `custom/Espo/Modules/Crm/Resources/routes.json`

Ce dossier doit respecter la même arborescence que le module core.

### 3) Exemple de modification simple

Pour modifier la logique d'une action `Lead` :

- Créez `custom/Espo/Modules/Crm/Controllers/Lead.php`
- Déclarez le même namespace `Espo\Modules\Crm\Controllers`
- Ajoutez ou surchargez la méthode souhaitée

Pour modifier des champs ou une vue standard :

- Créez `custom/Espo/Modules/Crm/Resources/metadata/entityDefs/Lead.json`
- Créez `custom/Espo/Modules/Crm/Resources/metadata/clientDefs/Lead.json`
- Créez `custom/Espo/Modules/Crm/Resources/metadata/layouts/Lead/detail.json`

### 4) Calendar et activités

La fonctionnalité `Calendar` n'est pas toujours une entité distincte : elle repose souvent sur les entités d'activité `Meeting`, `Call`, `Task` et les routes/actions CRM.

- Les objets `Call` et `Meeting` sont dans `application/Espo/Modules/Crm/Entities/`
- Le `calendar` UI peut être contrôlé par des metadata et des routes dans `Resources/`
- Si vous souhaitez changer l'affichage ou le comportement du calendrier, regardez d'abord `application/Espo/Modules/Crm/Resources/metadata/clientDefs/` et `Resources/layouts/`

### 5) Après modification

- Vider le cache : `php clear_cache.php`
- Rebuild si vous changez des metadata JSON : `php rebuild.php`
- Reload frontend si vous changez une vue JS custom

---

## Développer une fonctionnalité : les étapes

### 1. Définir l'entité (si nouvelle)

**Fichier** : `custom/Espo/Custom/Resources/metadata/scopes/MonEntite.json`
```json
{
    "entity": true,
    "object": true,
    "module": "Custom",
    "stream": false,
    "tab": true
}
```

**Fichier** : `custom/Espo/Custom/Resources/metadata/entityDefs/MonEntite.json`
```json
{
    "fields": {
        "name": { "type": "varchar", "required": true },
        "status": {
            "type": "enum",
            "options": ["New", "Active", "Closed"],
            "default": "New"
        },
        "montant": { "type": "currency" },
        "dateDebut": { "type": "date" }
    },
    "links": {
        "account": {
            "type": "belongsTo",
            "entity": "Account",
            "foreign": "monEntites"
        }
    }
}
```

### 2. Configurer l'interface (clientDefs)

**Fichier** : `custom/Espo/Custom/Resources/metadata/clientDefs/MonEntite.json`
```json
{
    "controller": "controllers/record",
    "views": {
        "list": "views/mon-entite/list",
        "detail": "views/mon-entite/detail"
    },
    "menu": {
        "list": {
            "buttons": [
                {
                    "label": "Action Custom",
                    "action": "monAction",
                    "acl": "edit"
                }
            ]
        }
    },
    "boolFilterList": ["onlyMy"],
    "kanbanViewMode": true
}
```

### 3. Ajouter un layout

**Fichier** : `custom/Espo/Custom/Resources/metadata/layouts/MonEntite/detail.json`
```json
[
    {
        "label": "Informations",
        "rows": [
            [{"name": "name"}, {"name": "status"}],
            [{"name": "montant"}, {"name": "dateDebut"}],
            [{"name": "account"}, false]
        ]
    }
]
```

### 4. Créer la logique métier (Service)

**Fichier** : `custom/Espo/Custom/Services/MonEntite.php`
```php
<?php
namespace Espo\Custom\Services;

use Espo\Core\Services\Base;

class MonEntite extends Base
{
    public function monAction(string $id): bool
    {
        $entity = $this->getEntityManager()->getEntity('MonEntite', $id);
        if (!$entity) return false;

        $entity->set('status', 'Active');
        $this->getEntityManager()->saveEntity($entity);

        return true;
    }
}
```

### 5. Exposer via un Controller (API)

**Fichier** : `custom/Espo/Custom/Controllers/MonEntite.php`
```php
<?php
namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;

class MonEntite extends \Espo\Core\Controllers\Record
{
    public function postActionMonAction(Request $request): bool
    {
        $data = $request->getParsedBody();
        $id = $data->id ?? null;

        return $this->getServiceFactory()
            ->create('MonEntite')
            ->monAction($id);
    }
}
```

**Route** (dans `routes.json`) :
```json
{
    "route": "/MonEntite/:id/monAction",
    "method": "post",
    "params": {
        "controller": "MonEntite",
        "action": "monAction"
    }
}
```

### 6. Ajouter un Hook (événement)

**Fichier** : `custom/Espo/Custom/Hooks/MonEntite/StatusChange.php`
```php
<?php
namespace Espo\Custom\Hooks\MonEntite;

use Espo\ORM\Entity;

class StatusChange
{
    public static int $order = 10;

    public function beforeSave(Entity $entity, array $options): void
    {
        if ($entity->isAttributeChanged('status')) {
            $entity->set('dateModification', date('Y-m-d'));
        }
    }

    public function afterSave(Entity $entity, array $options): void
    {
        if ($entity->get('status') === 'Closed') {
            // Logique post-fermeture
        }
    }
}
```

### 7. Créer une vue frontend

**Fichier** : `client/custom/src/views/mon-entite/detail.js`
```javascript
define('custom:views/mon-entite/detail', ['views/detail'], function (Detail) {
    return Detail.extend({

        setup: function () {
            Detail.prototype.setup.call(this);

            this.addMenuItem('buttons', {
                label: 'Mon Action',
                action: 'monAction',
                acl: 'edit'
            });
        },

        actionMonAction: function () {
            this.confirm('Confirmer ?', () => {
                Espo.Ajax.postRequest('MonEntite/' + this.model.id + '/monAction')
                    .then(() => {
                        this.model.fetch();
                        Espo.Ui.success('Action effectuée');
                    });
            });
        }
    });
});
```

### 8. Ajouter les traductions

**Fichier** : `custom/Espo/Custom/Resources/i18n/fr_FR/MonEntite.json`
```json
{
    "labels": {
        "Create MonEntite": "Créer une entrée",
        "Mon Action": "Mon Action"
    },
    "fields": {
        "name": "Nom",
        "status": "Statut",
        "montant": "Montant",
        "dateDebut": "Date de début"
    },
    "options": {
        "status": {
            "New": "Nouveau",
            "Active": "Actif",
            "Closed": "Fermé"
        }
    }
}
```

---

## Commandes utiles

| Action | Commande |
|--------|----------|
| Clear cache (dev) | `docker exec espocrm-dev-app php clear_cache.php` |
| Rebuild (dev) | `docker exec espocrm-dev-app php rebuild.php` |
| Clear cache (prod) | `docker exec espocrm-app php clear_cache.php` |
| Rebuild (prod) | `docker exec espocrm-app php rebuild.php` |

---

## Quand faire quoi ?

| Modification | Clear Cache | Rebuild | Rebuild Docker |
|---|---|---|---|
| Fichier PHP (hook, service, controller) | ✅ | ❌ | ❌ |
| Metadata JSON (entityDefs, clientDefs) | ✅ | ✅ | ❌ |
| Layout JSON | ✅ | ✅ | ❌ |
| Traductions i18n | ✅ | ✅ | ❌ |
| Vue JavaScript (client/custom) | ❌ (Ctrl+F5) | ❌ | ❌ |
| Dockerfile / docker-compose | ❌ | ❌ | ✅ |
| Ajout dépendance Composer | ❌ | ❌ | ✅ |

---

## Workflow type pour une nouvelle fonctionnalité

```
1. Définir les champs        → entityDefs/MonEntite.json
2. Déclarer l'entité         → scopes/MonEntite.json
3. Configurer l'interface    → clientDefs/MonEntite.json
4. Créer les layouts         → layouts/MonEntite/
5. Ajouter les traductions   → i18n/fr_FR/MonEntite.json
6. Logique métier            → Services/MonEntite.php
7. API custom                → Controllers/MonEntite.php + routes.json
8. Hooks (si événements)     → Hooks/MonEntite/
9. Vues custom (si besoin)   → client/custom/src/views/mon-entite/
10. Clear cache + Rebuild
```

---

## Bonnes pratiques

- **Toujours travailler dans `custom/`** — ne jamais modifier les fichiers core
- **Un hook = une responsabilité** — séparer les hooks par logique
- **Nommer les fichiers en PascalCase** côté PHP, en kebab-case côté JS
- **Tester en dev** (`localhost:8085`) avant de déployer en prod
- **Versionner avec Git** — commit après chaque fonctionnalité testée
- **Clear cache après chaque modif PHP/JSON** pour voir les changements


---

## Intégration d'une API externe : exemples concrets

### Pattern général d'intégration API

```
1. Credentials       → .env (clés API, secrets)
2. Controller        → custom/Espo/Custom/Controllers/MonApi.php
3. Service (optionnel) → custom/Espo/Custom/Services/MonApiService.php
4. Routes            → custom/Espo/Custom/Resources/routes.json
5. Vue frontend      → client/custom/src/views/mon-dashboard.js
6. Template HTML     → client/custom/res/templates/mon-dashboard.tpl
7. clientDefs        → metadata/clientDefs/MonDashboard.json
```

**Cas concrets ajoutés :**

- Google Calendar : OAuth2, token refresh, synchronisation des événements vers `Meeting`.
- Google Maps : clé API simple, récupération d'adresses, affichage via une vue `MapData`.

---

### Exemple 1 : Intégration Aircall (téléphonie)

**Principe** : Proxy les appels vers l'API Aircall, reçoit les webhooks pour logger les appels.

#### Étape 1 — Credentials dans `.env`

```env
AIRCALL_ID=votre_api_id
AIRCALL_TOKEN=votre_api_token
```

#### Étape 2 — Controller (point d'entrée API)

**Fichier** : `custom/Espo/Custom/Controllers/Aircall.php`

```php
<?php
namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Utils\Config;

class Aircall
{
    public function __construct(
        private Config $config,
        private AircallCallLoggingService $aircallCallLoggingService
    ) {}

    // GET /api/v1/Aircall/calls — Récupérer les appels
    public function getActionGetCalls(Request $request, Response $response): void
    {
        $credentials = $this->loadCredentials();
        $url = "https://api.aircall.io/v1/calls?per_page=50";
        $result = $this->aircallRequest('GET', $url, $credentials);

        $response->writeBody(json_encode([
            'status' => 'success',
            'calls' => $result['data']['calls'] ?? [],
        ]));
    }

    // POST /api/v1/Aircall/dial — Lancer un appel
    public function postActionDial(Request $request, Response $response): void
    {
        $body = $request->getParsedBody();
        $number = $this->normalizeNumber($body->number);
        $userId = $body->userId;

        $credentials = $this->loadCredentials();
        $url = "https://api.aircall.io/v1/users/{$userId}/calls";
        $result = $this->aircallRequest('POST', $url, $credentials, ['number' => $number]);

        $response->writeBody(json_encode(['status' => 'success']));
    }

    // POST /api/v1/aircall-webhook — Recevoir les événements Aircall
    public function postActionWebhook(Request $request, Response $response): void
    {
        $body = json_decode(json_encode($request->getParsedBody()), true);
        $event = $body['event'] ?? null;

        // Logger l'appel dans EspoCRM selon l'événement
        if ($event === 'call.created') {
            $this->aircallCallLoggingService->handleCallCreated($body);
        } elseif ($event === 'call.ended') {
            $this->aircallCallLoggingService->handleCallEnded($body);
        }

        $response->writeBody(json_encode(['status' => 'success']));
    }

    // Méthode utilitaire : appel cURL vers Aircall
    private function aircallRequest(string $method, string $url, array $creds, ?array $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $creds['id'] . ':' . $creds['token']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'POST' && $body) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['httpCode' => $httpCode, 'data' => json_decode($response, true)];
    }
}
```

#### Étape 3 — Routes

```json
{"route": "/Aircall/calls", "method": "get", "params": {"controller": "Aircall", "action": "getCalls"}},
{"route": "/Aircall/dial", "method": "post", "params": {"controller": "Aircall", "action": "dial"}},
{"route": "/aircall-webhook", "method": "post", "params": {"controller": "Aircall", "action": "webhook"}, "conditions": {"auth": false}}
```

> Note : `"conditions": {"auth": false}` permet au webhook d'être appelé sans authentification EspoCRM.

#### Étape 4 — Appel depuis le frontend

```javascript
// Lancer un appel depuis une vue
Espo.Ajax.postRequest('Aircall/dial', {
    number: '+33612345678',
    userId: 12345  // ID utilisateur Aircall
}).then(() => {
    Espo.Ui.success('Appel lancé');
});
```

---

### Exemple 2 : Intégration Google Calendar (OAuth2)

**Principe** : Authentification OAuth2, sync bidirectionnelle des événements.

**Résumé de l'intégration Google Calendar** :

- Ajouter les identifiants Google dans `data/config.php` ou `.env`.
- Créer un contrôleur custom dans `custom/Espo/Custom/Controllers/` pour gérer la redirection OAuth, le callback et la synchronisation.
- Définir les routes API custom dans `custom/Espo/Custom/Resources/routes.json`.
- Stocker les tokens dans un fichier sécurisé (`data/google-calendar-tokens.json`).
- Créer une vue frontend et des boutons d'action si nécessaire dans `client/custom/src/views/`.

---

### Exemple 3 : Intégration Google Maps (API Key simple)

**Principe** : Pas d'OAuth, juste une clé API. Récupère les coordonnées des Leads/Clients pour afficher sur une carte.

#### Étape 1 — Credentials

```env
GOOGLE_CLIENT_ID=878189...apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
```

+ dans `data/config.php` :
```php
'googleClientId' => '...',
'googleClientSecret' => '...',
```

#### Étape 2 — Flux OAuth2

```
Utilisateur clique "Connecter Google"
    → Controller redirige vers Google (consent screen)
    → Google redirige vers callback avec ?code=xxx
    → Controller échange le code contre access_token + refresh_token
    → Tokens stockés dans data/google-calendar-tokens.json
```

**Controller simplifié** :

```php
<?php
namespace Espo\Custom\Controllers;

class GoogleCalendar
{
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    // Rediriger vers Google pour autorisation
    public function getActionRedirectToGoogle(Request $request, Response $response): void
    {
        $params = [
            'client_id' => $this->config->get('googleClientId'),
            'redirect_uri' => $siteUrl . '/?entryPoint=GoogleCalendar&action=handleGoogleCallback',
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
        $response->setHeader('Location', 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        $response->setStatus(302);
    }

    // Callback : échanger le code contre des tokens
    public function getActionHandleGoogleCallback(Request $request, Response $response): void
    {
        $code = $request->getQueryParam('code');
        $tokenData = $this->exchangeCodeForTokens($code);
        $this->saveTokens($tokenData);
        // Rediriger vers le calendrier
        $response->setHeader('Location', $siteUrl . '/#Calendar');
        $response->setStatus(302);
    }

    // Sync : récupérer les événements Google → créer des Meetings
    public function postActionSyncEvents(Request $request, Response $response): void
    {
        $accessToken = $this->getValidAccessToken();
        $events = $this->curlGet(self::GOOGLE_CALENDAR_API . '/calendars/primary/events', $accessToken);

        foreach ($events['items'] as $event) {
            $this->syncEventToMeeting($event);  // Crée ou met à jour un Meeting
        }
    }

    // Push : envoyer un Meeting EspoCRM → Google Calendar
    public function postActionPushEvent(Request $request, Response $response): void
    {
        $meetingId = $request->getParsedBody()->meetingId;
        $meeting = $this->entityManager->getEntityById('Meeting', $meetingId);
        $accessToken = $this->getValidAccessToken();

        $eventData = [
            'summary' => $meeting->get('name'),
            'start' => ['dateTime' => date('c', strtotime($meeting->get('dateStart')))],
            'end' => ['dateTime' => date('c', strtotime($meeting->get('dateEnd')))],
        ];

        $this->curlPostJson(self::GOOGLE_CALENDAR_API . '/calendars/primary/events', $eventData, $accessToken);
    }
}
```

#### Étape 3 — Gestion du refresh token

```php
private function getValidAccessToken(array $tokens): string
{
    $expiresAt = $tokens['obtained_at'] + $tokens['expires_in'];

    if (time() >= $expiresAt - 60) {
        // Token expiré → refresh
        $newTokens = $this->curlPost(self::GOOGLE_TOKEN_URL, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $tokens['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);
        $this->saveTokens($newTokens);
        return $newTokens['access_token'];
    }

    return $tokens['access_token'];
}
```

---

### Exemple 3 : Intégration Google Maps (API Key simple)

**Principe** : Pas d'OAuth, juste une clé API. Récupère les coordonnées des Leads/Clients pour afficher sur une carte.

**Résumé de l'intégration Google Maps** :

- Ajouter la clé API dans `data/config.php`.
- Créer un controller custom dans `custom/Espo/Custom/Controllers/MapData.php`.
- Ajouter une route dans `custom/Espo/Custom/Resources/routes.json` pour exposer les données de localisation.
- Créer une vue frontend dans `client/custom/src/views/` pour afficher la carte et charger les marqueurs.

#### Étape 1 — Clé API

Dans `data/config.php` :
```php
'googleMapsApiKey' => 'AIzaSy...',
```

#### Étape 2 — Controller backend

**Fichier** : `custom/Espo/Custom/Controllers/MapData.php`

```php
<?php
namespace Espo\Custom\Controllers;

class MapData
{
    // GET /api/v1/MapData/locations
    public function getActionLocations(Request $request, Response $response): void
    {
        $locations = [];

        // Récupérer les Leads avec une adresse
        $leads = $this->entityManager->getRDBRepository('Lead')
            ->where(['addressCity!=' => null])
            ->find();

        foreach ($leads as $lead) {
            $address = $lead->get('addressStreet') . ' ' . $lead->get('addressCity');
            $locations[] = [
                'id' => $lead->getId(),
                'type' => 'Lead',
                'name' => $lead->get('name'),
                'address' => $address,
            ];
        }

        $response->writeBody(json_encode(['data' => $locations]));
    }
}
```

#### Étape 3 — Vue frontend avec Google Maps

```javascript
define('custom:views/maps-dashboard', ['view'], function (View) {
    return View.extend({
        template: 'custom:maps-dashboard',

        afterRender: function () {
            // Charger les données
            Espo.Ajax.getRequest('MapData/locations').then(result => {
                this.initMap(result.data);
            });
        },

        initMap: function (locations) {
            var map = new google.maps.Map(this.$el.find('.map-container')[0], {
                zoom: 6,
                center: {lat: 46.6, lng: 2.3}  // Centre France
            });

            locations.forEach(loc => {
                // Geocoder l'adresse puis placer un marqueur
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({address: loc.address}, (results, status) => {
                    if (status === 'OK') {
                        new google.maps.Marker({
                            map: map,
                            position: results[0].geometry.location,
                            title: loc.name
                        });
                    }
                });
            });
        }
    });
});
```

---

## Résumé des patterns d'authentification API

| API | Auth | Stockage credentials |
|-----|------|---------------------|
| Aircall | Basic Auth (ID:Token) | `.env` |
| Google Calendar | OAuth2 (access + refresh token) | `data/google-calendar-tokens.json` |
| Google Maps | API Key (dans URL) | `data/config.php` |
| Webhook entrant | Aucune (`"auth": false` dans route) | — |

---

## Checklist intégration API

```
□ Credentials dans .env ou config.php
□ Controller avec méthodes get/post Action
□ Service si logique complexe
□ Routes dans routes.json
□ Gestion des erreurs (try/catch, codes HTTP)
□ Timeout cURL (30s recommandé)
□ Refresh token si OAuth2
□ Vue frontend pour l'interface utilisateur
□ Traductions i18n
□ Clear cache + Rebuild
□ Test en dev avant déploiement prod
```
