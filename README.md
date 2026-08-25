# StockFlow API 

API REST de gestion de stock : catalogue produits, clients, commandes et mouvements de stock à la confirmation.

Architecture en couches : `Request → DTO → Repository → Service → Controller → Resource`.

---

## Prérequis

| Outil | Version |
|---|---|
| PHP | ≥ 8.3 (avec `bcmath`, `pdo_sqlite`) |
| Composer | 2.x |
| Node.js | optionnel (aucun asset front nécessaire pour l'API) |

## Installation

```bash
git clone <url-du-repo>
cd StockFlowAPI
composer install
```

## Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Variables utiles :

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
L5_SWAGGER_GENERATE_ALWAYS=true   # régénère la doc à chaque affichage (dev)
```

## Base de données

SQLite par défaut, aucun serveur à installer :

```bash
touch database/database.sqlite
```

Pour MySQL, remplacer dans `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockflow
DB_USERNAME=root
DB_PASSWORD=
```

## Migrations

```bash
php artisan migrate          # applique les migrations en attente
php artisan migrate:fresh    # supprime toutes les tables et remigre
php artisan migrate:status   # état des migrations
```

## Seeders

Jeu de données cohérent : **20 produits, 10 clients, 7 commandes, 16 lignes**.

```bash
php artisan migrate:fresh --seed
```

Les commandes sont créées **via `OrderService`**, pas par insertion brute : les totaux sont calculés depuis les lignes et le stock des commandes confirmées est réellement décrémenté. Statuts couverts : 2 `draft`, 3 `confirmed`, 2 `cancelled` (dont une confirmée puis annulée, stock restitué).

## Lancement

```bash
php artisan serve
```

→ `http://127.0.0.1:8000` — l'API est préfixée par `/api/v1`.

## Tests

```bash
php artisan test
```

⚠️ **Aucun test applicatif n'est écrit à ce jour** (voir *Non terminé*). Seuls les `ExampleTest` de Laravel sont présents. Les 4 factories (`Product`, `Customer`, `Order`, `OrderItem`) sont prêtes et attendent leurs tests.

## Swagger

```bash
php artisan l5-swagger:generate
```

→ **http://127.0.0.1:8000/api/documentation** (JSON brut : `/docs?api-docs.json`)

La documentation est écrite en **attributs PHP 8** (`#[OA\Get]`), pas en annotations docblock : swagger-php 6 a retiré le support des docblocks, qui exigent le paquet abandonné `doctrine/annotations`.

---

## Endpoints

| Ressource | Routes |
|---|---|
| Produits | `GET POST /products` · `GET PATCH DELETE /products/{id}` |
| Clients | `GET POST /customers` · `GET PATCH DELETE /customers/{id}` |
| Commandes | `GET POST /orders` · `GET PATCH DELETE /orders/{id}` |
| Transitions | `POST /orders/{id}/confirm` · `POST /orders/{id}/cancel` |
| Lignes | `GET POST /orders/{id}/items` · `GET PATCH DELETE /orders/{id}/items/{item}` |

`{id}` est toujours le **`public_id` (ULID)**, jamais l'auto-increment interne.

---

## `ApiResponse`

`app/Http/Responses/ApiResponse.php` — point d'entrée unique de toutes les réponses JSON.

```php
use App\Http\Responses\ApiResponse;

ApiResponse::success(data: ProductResource::make($product), message: 'Produit trouvé.');
ApiResponse::created(data: ..., message: 'Commande créée avec succès.');
ApiResponse::noContent();                                    // 204, sans corps
ApiResponse::error(message: '...', errorCode: ErrorCode::InsufficientStock);
ApiResponse::validationError($errors);                       // 422
ApiResponse::notFound();                                     // 404
```

**Enveloppe de succès :**

```json
{
  "success": true,
  "message": "Produit créé avec succès.",
  "data": { "id": "01m0t7zz…", "name": "Vis M6 inox", "unitPrice": 12.5 }
}
```

La pagination d'une `ResourceCollection` est extraite automatiquement vers `meta` (compteurs) et `links` (URLs) — elle n'est jamais perdue par l'enveloppage.

## `ErrorResponse`

La structure d'erreur est produite par `ApiResponse::error()` ; sa **taxonomie** vit dans `app/Enums/ErrorCode.php`, et les exceptions qui la portent dérivent de `app/Exceptions/ApiException.php`.

```json
{
  "success": false,
  "message": "Stock insuffisant pour confirmer la commande.",
  "error_code": "INSUFFICIENT_STOCK",
  "errors": { "items": ["Produit \"Vis M6\" : 99 demandé(s), 5 disponible(s)."] }
}
```

| Catégorie | HTTP | `error_code` |
|---|---|---|
| Validation | 422 | `VALIDATION_ERROR` |
| Ressource inexistante | 404 | `RESOURCE_NOT_FOUND` |
| Authentification | 401 | `UNAUTHENTICATED` |
| Autorisation | 403 | `FORBIDDEN` |
| Méthode interdite | 405 | `METHOD_NOT_ALLOWED` |
| Conflit / métier | 409 | `CONFLICT`, `BUSINESS_RULE_VIOLATION`, `INSUFFICIENT_STOCK`, `INVALID_ORDER_TRANSITION` |
| Technique imprévue | 500 | `SERVER_ERROR` |

**Déclarer une nouvelle erreur métier** — 6 lignes, le rendu JSON est hérité :

```php
class CustomerSuspendedException extends ApiException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::BusinessRule;
    }
}
```

Laravel appelle `render()` automatiquement : aucun enregistrement dans le handler n'est nécessaire.

`bootstrap/app.php` mappe en plus les exceptions natives de Laravel (validation, 404, 405, 401, 403) et pose un **filet `Throwable`** en dernier pour les erreurs imprévues. Ce filet se retire quand `APP_DEBUG=true` afin de conserver la trace en local, et n'expose jamais la stack trace en production.

---

## Décisions techniques

**`public_id` (ULID) exposé, `id` masqué.** Chaque table a un auto-increment interne pour les jointures et un ULID public pour l'API. Les URLs et le JSON n'exposent jamais l'`id` — pas d'énumération possible du catalogue ou des clients.

**snake_case en base, camelCase en JSON.** La traduction est faite par les Resources : la base reste idiomatique Laravel, l'API idiomatique JavaScript.

**Montants en `decimal(12,2)` et `bcmath`.** Jamais de `float` : `0.1 + 0.2 !== 0.3` produit des écarts comptables sur les totaux de commande.

**`unit_price` copié sur la ligne à la commande.** Un changement de tarif ne réécrit pas les commandes passées.

**`total_amount` jamais envoyé par le client.** Calculé depuis les lignes, recalculé à chaque ajout/modification/suppression, dans la même transaction.

**Le stock bouge à la confirmation.** `POST /orders/{id}/confirm` vérifie **toutes** les lignes avant d'écrire quoi que ce soit — une commande partiellement servable ne laisse jamais le stock à moitié décrémenté. Les lignes produit sont verrouillées (`lockForUpdate`) pour empêcher la survente concurrente. `POST /cancel` restitue les quantités.

**Une commande n'est modifiable qu'en `draft`.** Confirmée, elle engage du stock ; annulée, elle est une archive. Toute modification de l'en-tête ou des lignes renvoie 409. `status` est également refusé sur `PATCH /orders/{id}` : sans ce garde-fou, on aurait pu basculer une commande en `confirmed` sans déclencher le mouvement de stock.

**`status` produit et `available_quantity` sont indépendants.** `status` porte l'intention commerciale (`archived`…), la quantité porte le stock physique. Aucune synchronisation automatique — choix assumé.

**Interfaces liées dans `AppServiceProvider`.** Les 4 repositories et les 4 services sont résolus par le conteneur ; les contrôleurs ne connaissent que les interfaces.

---

## Temps consacré au projet

> **À compléter par le développeur.** _(ex. : ~X h réparties sur N jours)_

---

## Fonctionnalités terminées

- ✅ 4 modèles (`Product`, `Customer`, `Order`, `OrderItem`) avec relations, casts, ULID public
- ✅ 3 enums métier + 1 enum de codes d'erreur
- ✅ Migrations complètes avec clés étrangères (`cascade` / `restrict`)
- ✅ 4 repositories (interface + implémentation) liés au conteneur
- ✅ 4 services (interface + implémentation) portant la logique métier
- ✅ DTOs et FormRequests pour chaque entité
- ✅ Resources JSON avec relations conditionnelles
- ✅ **22 endpoints** REST versionnés `/api/v1`
- ✅ Confirmation / annulation de commande avec mouvement de stock transactionnel
- ✅ Verrouillage des commandes non-`draft`
- ✅ `ApiResponse` + `ErrorCode` + `ApiException` : enveloppe unique en succès et en erreur
- ✅ Gestion globale des exceptions, filet `Throwable` inclus
- ✅ Documentation OpenAPI complète (Swagger UI)
- ✅ Seeders cohérents + 4 factories

## Non terminé / améliorations possibles

- ❌ **Tests automatisés** — aucun test applicatif écrit. Priorité n° 1 : les règles de stock et de transition ne sont protégées par rien.
- ❌ **Authentification** — Sanctum est installé (table `personal_access_tokens` migrée) mais **aucune route n'est protégée**. `authorize()` renvoie `true` partout. À faire avant toute mise en ligne.
- ❌ **Rate limiting** — `withMiddleware()` est vide, aucune limite de débit.
- ⬜ Filtres et tri sur les listes (`?status=`, `?search=`, `?sort=`)
- ⬜ `customer_id` fuit encore dans le JSON des commandes — à remplacer par le `public_id` du client dans `OrderResource`
- ⬜ Références de commande aléatoires (`ORD-` + 10 caractères) plutôt que séquentielles ; une collision lèverait une erreur SQL sans retry
- ⬜ Règle métier absente : un client `suspended` peut toujours commander
- ⬜ Historique des mouvements de stock (table dédiée) plutôt qu'un simple compteur
- ⬜ CI (GitHub Actions) exécutant `pint` et `php artisan test`
