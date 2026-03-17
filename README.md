# Todo List API — Symfony 6.4 (For Laravel Developers)

A production-ready Symfony 6.4 REST API with JWT authentication, Swagger documentation, async messaging, and Redis caching. Built as a **learning project for Laravel developers** transitioning to Symfony.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Project Structure Explained](#project-structure-explained)
3. [Laravel vs Symfony — Core Concepts](#laravel-vs-symfony--core-concepts)
4. [Authentication (JWT)](#authentication-jwt)
5. [Todo CRUD API](#todo-crud-api)
6. [DTOs & Validation](#dtos--validation)
7. [Doctrine ORM vs Eloquent](#doctrine-orm-vs-eloquent)
8. [Symfony Messenger (Async)](#symfony-messenger-async)
9. [Security Deep Dive](#security-deep-dive)
10. [Caching with Redis](#caching-with-redis)
11. [Swagger / OpenAPI](#swagger--openapi)
12. [Exception Handling](#exception-handling)
13. [Testing](#testing)
14. [API Examples](#api-examples)
15. [Commands Reference](#commands-reference)

---

## Quick Start

### Option A: Docker (Recommended)

```bash
# Clone the repo
git clone https://github.com/oleynikVlad/todo-symfony.git
cd todo-symfony

# Start all services (PHP, PostgreSQL, Redis)
docker compose -f docker-compose.yml up -d --build

# Inside the PHP container:
docker exec -it todo_php bash

# Generate JWT keys (first time only)
php bin/console lexik:jwt:generate-keypair --overwrite

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# The API is available at http://localhost:8080
# Swagger UI: http://localhost:8080/api/doc
```

### Option B: Local Development

```bash
# Prerequisites: PHP 8.2+, Composer, PostgreSQL, Redis

# Install dependencies
composer install

# Configure .env.local with your database credentials
# DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/todo_db?serverVersion=16&charset=utf8"
# REDIS_URL=redis://127.0.0.1:6379

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# Start the development server
php -S 127.0.0.1:8080 -t public

# Swagger UI: http://127.0.0.1:8080/api/doc
```

---

## Project Structure Explained

```
todo-symfony/
├── config/                          # All configuration (YAML)
│   ├── bundles.php                  # Registered bundles (≈ Laravel's config/app.php providers)
│   ├── packages/
│   │   ├── cache.yaml               # Cache config (≈ config/cache.php)
│   │   ├── doctrine.yaml            # ORM config (≈ config/database.php)
│   │   ├── messenger.yaml           # Async queue config (≈ config/queue.php)
│   │   ├── nelmio_api_doc.yaml      # Swagger config
│   │   └── security.yaml            # Auth config (≈ config/auth.php)
│   ├── routes.yaml                  # Route loading (≈ routes/api.php)
│   └── services.yaml               # DI container config (≈ AppServiceProvider)
│
├── src/                             # Application source code
│   ├── Controller/                  # HTTP controllers (≈ app/Http/Controllers)
│   │   ├── AuthController.php       # Register + Login endpoints
│   │   └── TodoController.php       # CRUD endpoints
│   ├── DTO/                         # Data Transfer Objects (≈ app/Http/Resources + FormRequests)
│   │   ├── CreateTodoRequest.php    # Input DTO with validation
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── UpdateTodoRequest.php
│   │   ├── TodoResponse.php         # Output DTO (≈ JsonResource)
│   │   └── PaginatedResponse.php
│   ├── Entity/                      # Doctrine entities (≈ app/Models)
│   │   ├── User.php
│   │   └── Todo.php
│   ├── EventListener/               # Event listeners (≈ app/Listeners)
│   │   └── ExceptionListener.php    # Global exception handler (≈ app/Exceptions/Handler.php)
│   ├── Message/                     # Async messages (≈ app/Jobs or app/Events)
│   │   └── TodoCreatedNotification.php
│   ├── MessageHandler/              # Message handlers (≈ app/Listeners or Job::handle())
│   │   └── TodoCreatedNotificationHandler.php
│   ├── Repository/                  # Data access layer (≈ query scopes / custom queries)
│   │   ├── UserRepository.php
│   │   └── TodoRepository.php
│   └── Service/                     # Business logic layer (≈ app/Services or Actions)
│       ├── AuthService.php
│       └── TodoService.php
│
├── tests/                           # Test suite
│   ├── Unit/                        # Unit tests
│   └── Integration/                 # Integration/functional tests
│
├── docker/                          # Docker configuration
│   └── php/Dockerfile
├── docker-compose.yml               # Docker services
├── .env                             # Environment variables (≈ Laravel's .env)
└── composer.json                    # Dependencies (≈ composer.json)
```

### Key Differences in Structure

| Laravel | Symfony | Purpose |
|---------|---------|---------|
| `app/Models/` | `src/Entity/` | Database models/entities |
| `app/Http/Controllers/` | `src/Controller/` | HTTP request handlers |
| `app/Http/Requests/` | `src/DTO/` | Input validation |
| `app/Http/Resources/` | `src/DTO/` | Output transformation |
| `app/Services/` | `src/Service/` | Business logic |
| `app/Repositories/` | `src/Repository/` | Database queries |
| `app/Events/` | `src/Message/` | Event/message classes |
| `app/Listeners/` | `src/MessageHandler/` | Event/message handlers |
| `app/Exceptions/Handler.php` | `src/EventListener/ExceptionListener.php` | Exception handling |
| `config/*.php` | `config/packages/*.yaml` | Configuration |
| `routes/api.php` | Attributes on controllers | Route definitions |
| `database/migrations/` | `migrations/` | Database migrations |

---

## Laravel vs Symfony — Core Concepts

### 1. Service Container (Dependency Injection)

**Laravel:**
```php
// In a service provider
$this->app->bind(TodoService::class, function ($app) {
    return new TodoService($app->make(TodoRepository::class));
});

// Or use automatic injection in controllers
public function index(TodoService $todoService) { ... }
```

**Symfony:**
```yaml
# config/services.yaml — autowiring handles everything automatically
services:
    _defaults:
        autowire: true       # Inject dependencies by type-hint
        autoconfigure: true  # Auto-register services as commands, listeners, etc.

    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Kernel.php'
```

**Key Insight:** Symfony autowires ALL classes in `src/` by default. Just type-hint your dependency in the constructor and it works. No manual binding needed in most cases.

---

### 2. Controllers

**Laravel:**
```php
class TodoController extends Controller
{
    public function store(StoreTodoRequest $request)
    {
        $todo = Todo::create($request->validated());
        return new TodoResource($todo);
    }
}

// Routes in routes/api.php:
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('todos', TodoController::class);
});
```

**Symfony:**
```php
#[Route('/api/todos')]
class TodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_todos_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = new CreateTodoRequest();
        // ... validate and create
        return $this->json($data, Response::HTTP_CREATED);
    }
}
```

**Key Differences:**
- Laravel: Routes defined in `routes/api.php` (separate files)
- Symfony: Routes defined as **PHP attributes** on controller methods
- Laravel: `$request->validated()` returns validated array
- Symfony: Manual DTO hydration + `ValidatorInterface::validate()`
- Laravel: `response()->json()` or resource classes
- Symfony: `$this->json()` (from AbstractController) or `new JsonResponse()`

---

### 3. Configuration

**Laravel:** PHP arrays in `config/` directory
```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
        ],
    ],
];
```

**Symfony:** YAML files in `config/packages/`
```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        auto_mapping: true
```

**Key Insight:** Symfony uses `%env(VAR)%` syntax to reference environment variables in YAML. Laravel uses `env('VAR')` PHP function. Both read from `.env` files.

---

### 4. Middleware vs Kernel Events

**Laravel Middleware:**
```php
// app/Http/Middleware/EnsureTokenIsValid.php
class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasHeader('Authorization')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
```

**Symfony Kernel Events:**
```php
// Symfony uses an event-driven approach instead of middleware chains
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class TokenValidationListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->headers->has('Authorization')) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized'], 401));
        }
    }
}
```

**Key events in Symfony (equivalent to Laravel middleware pipeline):**
- `kernel.request` — Before controller (like `before` middleware)
- `kernel.controller` — After routing, before action
- `kernel.response` — After controller (like `after` middleware)
- `kernel.exception` — On error (like exception handler)
- `kernel.terminate` — After response sent (like terminable middleware)

---

## Authentication (JWT)

### How It Works

1. **Register:** POST `/api/register` → creates user with hashed password
2. **Login:** POST `/api/login` → validates credentials → returns JWT token
3. **Protected routes:** Include `Authorization: Bearer <token>` header

### Security Flow (Step by Step)

```
Request → Firewall → Authenticator (JWT) → User Provider → Controller
                                                              ↓
Response ← Access Control ← Voter/Role Check ← Controller Response
```

**Laravel equivalent flow:**
```
Request → Middleware (auth:sanctum) → Guard → Provider → Controller
                                                           ↓
Response ← Controller Response
```

### Configuration

See `config/packages/security.yaml` for detailed comments comparing each section to Laravel's `config/auth.php`.

---

## Todo CRUD API

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:---:|
| POST | `/api/register` | Register new user | No |
| POST | `/api/login` | Login, get JWT token | No |
| GET | `/api/todos` | List todos (paginated) | Yes |
| POST | `/api/todos` | Create a todo | Yes |
| GET | `/api/todos/{id}` | Get single todo | Yes |
| PUT/PATCH | `/api/todos/{id}` | Update a todo | Yes |
| DELETE | `/api/todos/{id}` | Delete a todo | Yes |
| GET | `/api/doc` | Swagger UI | No |

### Filtering & Sorting

```bash
# Filter by status
GET /api/todos?status=pending

# Filter by date range
GET /api/todos?date_from=2024-01-01&date_to=2024-12-31

# Sort by created_at
GET /api/todos?sort=asc   # oldest first
GET /api/todos?sort=desc  # newest first (default)

# Pagination
GET /api/todos?page=2&limit=20

# Combine filters
GET /api/todos?status=completed&sort=asc&page=1&limit=5
```

---

## DTOs & Validation

### Why DTOs?

Both Laravel and Symfony have the concept of separating input validation from business logic:

**Laravel approach:** FormRequest classes
```php
class StoreTodoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'in:pending,in_progress,completed',
        ];
    }
}
```

**Symfony approach:** DTO with validation attributes
```php
class CreateTodoRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Choice(choices: Todo::VALID_STATUSES)]
    public string $status = Todo::STATUS_PENDING;
}
```

**Key Difference:**
- Laravel: Validation rules are strings (`'required|string|max:255'`)
- Symfony: Validation rules are PHP attributes (`#[Assert\NotBlank]`, `#[Assert\Length(max: 255)]`)
- Laravel FormRequests auto-validate before the controller runs
- Symfony requires manual validation: `$this->validator->validate($dto)`

---

## Doctrine ORM vs Eloquent

### Entity Definition

**Laravel (Eloquent — Active Record):**
```php
class Todo extends Model
{
    protected $fillable = ['title', 'description', 'status'];
    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Symfony (Doctrine — Data Mapper):**
```php
#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'todos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    // Explicit getters/setters for each property
}
```

### CRUD Operations

| Operation | Laravel (Eloquent) | Symfony (Doctrine) |
|-----------|-------------------|-------------------|
| Create | `Todo::create($data)` | `$em->persist($todo); $em->flush();` |
| Read | `Todo::find($id)` | `$repo->find($id)` |
| Update | `$todo->update($data)` | `$todo->setTitle('new'); $em->flush();` |
| Delete | `$todo->delete()` | `$em->remove($todo); $em->flush();` |
| Query | `Todo::where('status', 'pending')->get()` | `$repo->findBy(['status' => 'pending'])` |
| Paginate | `Todo::paginate(10)` | Custom QueryBuilder + Paginator |

### Key Concept: EntityManager & flush()

In Doctrine, changes are tracked in memory (Unit of Work pattern). **Nothing hits the database until you call `$entityManager->flush()`.**

```php
// Doctrine: Changes are batched
$todo1->setTitle('Updated 1');
$todo2->setTitle('Updated 2');
$entityManager->flush(); // Both updates execute in a single transaction

// Laravel: Each operation hits the DB immediately
$todo1->update(['title' => 'Updated 1']); // SQL executed
$todo2->update(['title' => 'Updated 2']); // SQL executed
```

### Migrations

```bash
# Laravel
php artisan make:migration create_todos_table
php artisan migrate

# Symfony (auto-generates migration from entity changes!)
php bin/console doctrine:migrations:diff    # Auto-detect changes
php bin/console doctrine:migrations:migrate
```

**Key Insight:** Symfony/Doctrine can auto-generate migrations by comparing your entity definitions to the current database schema. Laravel requires you to write migration files manually.

---

## Symfony Messenger (Async)

### How It Works

1. **Message class** — Plain PHP class holding data (like a Laravel Job or Event)
2. **Handler class** — Processes the message (like a Job's `handle()` method or Event Listener)
3. **Transport** — Where messages are stored (database, Redis, RabbitMQ)
4. **Routing** — Config that maps messages to transports

### Flow

```
Controller → dispatch(message) → Bus → Transport (doctrine/redis)
                                              ↓
Worker process (bin/console messenger:consume) → Handler
```

**Laravel equivalent:**
```
Controller → dispatch(Job) → Queue Driver (database/redis)
                                    ↓
Worker process (artisan queue:work) → Job::handle()
```

### Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            App\Message\TodoCreatedNotification: async
```

### Running the Worker

```bash
# Symfony
php bin/console messenger:consume async -vv

# Laravel equivalent
php artisan queue:work
```

---

## Security Deep Dive

### How Symfony Security Works Internally

```
                    ┌─────────────┐
HTTP Request ──────►│  Firewall   │
                    │ (pattern    │
                    │  matching)  │
                    └──────┬──────┘
                           │
              ┌────────────▼────────────┐
              │    Authenticator        │
              │ (JWT Token Extractor)   │
              │ Extracts token from     │
              │ Authorization header    │
              └────────────┬────────────┘
                           │
              ┌────────────▼────────────┐
              │    User Provider        │
              │ (Loads user from DB     │
              │  using email from JWT)  │
              └────────────┬────────────┘
                           │
              ┌────────────▼────────────┐
              │   Access Control        │
              │ (Checks ROLE_USER for   │
              │  /api/* routes)         │
              └────────────┬────────────┘
                           │
              ┌────────────▼────────────┐
              │    Controller           │
              │ ($this->getUser())      │
              └─────────────────────────┘
```

### Laravel Security Equivalent

```
HTTP Request → Middleware (auth:sanctum) → Guard (checks token)
    → UserProvider (loads from DB) → Controller (auth()->user())
```

### Roles & Access Control

**Symfony** uses a hierarchical role system:
```yaml
access_control:
    - { path: ^/api/(login|register), roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: ROLE_USER }
```

**Laravel** uses middleware:
```php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

---

## Caching with Redis

### Configuration

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            todo.cache:
                adapter: cache.app
                default_lifetime: 3600
```

### Usage

**Symfony:**
```php
use Symfony\Contracts\Cache\CacheInterface;

class TodoService
{
    public function __construct(private CacheInterface $cache) {}

    public function getCachedTodos(): array
    {
        return $this->cache->get('user_todos_' . $userId, function () {
            return $this->todoRepository->findBy(['owner' => $user]);
        });
    }
}
```

**Laravel equivalent:**
```php
Cache::remember('user_todos_' . $userId, 3600, function () {
    return Todo::where('user_id', $userId)->get();
});
```

---

## Swagger / OpenAPI

Access Swagger UI at: **`/api/doc`**

Documentation is generated automatically from PHP attributes on controller methods:

```php
#[OA\Post(
    summary: 'Create a new todo',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
    ]
)]
```

---

## Exception Handling

**Laravel:**
```php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ModelNotFoundException) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return parent::render($request, $e);
    }
}
```

**Symfony:**
```php
// src/EventListener/ExceptionListener.php
#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $response = new JsonResponse([
            'error' => true,
            'message' => $exception->getMessage(),
        ], $statusCode);
        $event->setResponse($response);
    }
}
```

---

## Testing

### Run Tests

```bash
# All tests
php bin/phpunit

# Unit tests only
php bin/phpunit tests/Unit/

# Integration tests only
php bin/phpunit tests/Integration/
```

### Test Types

| Type | Laravel | Symfony |
|------|---------|---------|
| Unit | `extends TestCase` | `extends TestCase` |
| Feature/Integration | `extends TestCase` (uses `RefreshDatabase`) | `extends WebTestCase` |
| HTTP assertions | `$this->postJson()` | `$client->request()` |
| Auth in tests | `$this->actingAs($user)` | Custom token/header setup |

---

## API Examples

### Register

```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "secret123"}'
```

Response:
```json
{
  "message": "User registered successfully.",
  "user": { "id": 1, "email": "user@example.com" }
}
```

### Login

```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "secret123"}'
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### Create Todo

```bash
curl -X POST http://localhost:8080/api/todos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"title": "Buy groceries", "description": "Milk, eggs, bread", "status": "pending"}'
```

### List Todos (with filters)

```bash
curl -X GET "http://localhost:8080/api/todos?status=pending&sort=desc&page=1&limit=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Update Todo

```bash
curl -X PUT http://localhost:8080/api/todos/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"status": "completed"}'
```

### Delete Todo

```bash
curl -X DELETE http://localhost:8080/api/todos/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Commands Reference

```bash
# Symfony Console (≈ php artisan)
php bin/console                              # List all commands
php bin/console cache:clear                  # Clear cache
php bin/console doctrine:migrations:diff     # Generate migration from entity changes
php bin/console doctrine:migrations:migrate  # Run migrations
php bin/console messenger:consume async      # Process async messages (≈ queue:work)
php bin/console debug:router                 # List all routes (≈ route:list)
php bin/console debug:container              # List all services
php bin/console lexik:jwt:generate-keypair   # Generate JWT RSA keys

# Testing
php bin/phpunit                              # Run test suite (≈ php artisan test)
```

| Laravel Command | Symfony Equivalent |
|----------------|-------------------|
| `php artisan serve` | `php -S 127.0.0.1:8080 -t public` or `symfony serve` |
| `php artisan migrate` | `php bin/console doctrine:migrations:migrate` |
| `php artisan make:migration` | `php bin/console doctrine:migrations:diff` |
| `php artisan make:model` | `php bin/console make:entity` |
| `php artisan make:controller` | `php bin/console make:controller` |
| `php artisan queue:work` | `php bin/console messenger:consume async` |
| `php artisan route:list` | `php bin/console debug:router` |
| `php artisan tinker` | `php bin/console` (no REPL equivalent built-in) |
| `php artisan test` | `php bin/phpunit` |
| `php artisan cache:clear` | `php bin/console cache:clear` |

---

## Architecture Summary

```
┌─────────────────────────────────────────────────────────────┐
│                        HTTP Request                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  Security Layer (Firewall + JWT Authenticator)              │
│  ≈ Laravel: auth:sanctum middleware                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  Controller (thin — delegates to Service layer)             │
│  - Parses request → DTO                                     │
│  - Validates DTO                                            │
│  - Calls Service                                            │
│  - Returns JsonResponse                                     │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  Service Layer (business logic)                             │
│  - Orchestrates Repository + EntityManager                  │
│  - Dispatches messages to MessageBus                        │
│  ≈ Laravel: Service classes or Actions                      │
└───────────┬──────────────────────────────┬──────────────────┘
            │                              │
┌───────────▼───────────┐    ┌─────────────▼──────────────────┐
│  Repository           │    │  Messenger Bus                  │
│  (Doctrine queries)   │    │  (async message dispatch)       │
│  ≈ Query scopes       │    │  ≈ Laravel queue/events         │
└───────────┬───────────┘    └─────────────┬──────────────────┘
            │                              │
┌───────────▼───────────┐    ┌─────────────▼──────────────────┐
│  Entity (plain PHP)   │    │  MessageHandler                 │
│  (Doctrine mapping)   │    │  (processes async work)         │
│  ≈ Eloquent Model     │    │  ≈ Job::handle() / Listener     │
└───────────────────────┘    └────────────────────────────────┘
```

---

## License

MIT
