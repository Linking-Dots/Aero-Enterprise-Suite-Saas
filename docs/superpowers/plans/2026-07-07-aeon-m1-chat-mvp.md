# Aeon M1 — Chat MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship an end-to-end "Ask Aeon anything" chat — a user opens a drawer (or the `/aeon` page), sends a message, and gets a Gemini-generated reply, with the conversation persisted.

**Architecture:** Provider-agnostic model layer (`AiProvider` contract in `aero-contracts`, `GeminiProvider` driver in `aero-assistant`). `AeonService` orchestrates one turn: persist the user message, call the provider, persist the assistant reply as generative-UI **blocks** (text-only in M1). Backend lives in `packages/aero-assistant`; Inertia page + drawer + button live in `packages/aero-ui` (the only place the host globs pages from).

**Tech Stack:** Laravel 12, PHP 8.2, Inertia v2 + React 18, `@aero/ui`, MySQL (prod) / in-memory SQLite (tests), Laravel `Http` facade for Gemini calls, orchestra/testbench + PHPUnit.

## Global Constraints

- **Package-first:** all backend logic in `packages/aero-assistant`; all Inertia pages/components in `packages/aero-ui`. Host apps only gain a composer require + `.env` keys.
- **Provider-agnostic:** feature code depends only on `Aero\Contracts\Ai\AiProvider`; never on Gemini specifics. Provider chosen by `config('aeon.provider')`.
- **Default models (verified 2026-07-07 on the live key):** chat `gemini-flash-latest`; embeddings `gemini-embedding-001` (M2). Endpoint base `https://generativelanguage.googleapis.com/v1beta`. Auth header `x-goog-api-key`.
- **Secrets:** `GEMINI_API_KEY` only in host `.env` (gitignored). Never commit a key. Never hardcode it.
- **Models** extend `Aero\Contracts\Models\TenantModel` — never bare `Model`.
- **Writes** in `DB::transaction()`.
- **Frontend:** 100% `@aero/ui` imports, no inline `style={}`, Inertia v2 `router.*`. Module brand name is **Aeon**; route prefix `/aeon`; module code `aeon`.
- **Generative UI:** assistant replies are a **list of typed blocks** (M1 uses only `{ type: 'text', text }`). Never raw HTML.
- **Tests:** every backend task is TDD (failing test first). Run via the package's `phpunit.xml` (bootstrap mirrors `packages/aero-auth/tests/bootstrap.php`).

---

## File Structure

**`packages/aero-contracts`** (the swappable interface):
- Create `src/Ai/AiProvider.php` — interface: `chat()`, `embed()`, `isAvailable()`.
- Create `src/Ai/AiChatResult.php` — immutable result DTO.

**`packages/aero-assistant`** (rebuilt off the bad scaffold):
- Modify `composer.json` — drop pgvector/guzzle, Laravel 12, provider class rename.
- Create `config/aeon.php` — provider + model config.
- Replace `config/module.php` — lean `aeon` module (chat submodule only for M1).
- Create `src/Providers/AeonServiceProvider.php` — binds `AiProvider`, registers `AeonService`.
- Create `src/Providers/Models/GeminiProvider.php` — `AiProvider` via `Http`.
- Create `src/Services/AeonService.php` — one chat turn.
- Create `src/Models/Conversation.php`, `src/Models/Message.php`.
- Create `database/migrations/2026_07_07_000001_create_aeon_conversations_table.php`.
- Create `database/migrations/2026_07_07_000002_create_aeon_messages_table.php`.
- Create `src/Http/Controllers/AeonController.php`, `src/Http/Controllers/AeonPageController.php`.
- Create `src/Http/Requests/SendMessageRequest.php`.
- Create `routes/web.php` (replace the old scaffold's).
- Create `phpunit.xml`, `tests/bootstrap.php`, `tests/PackageTestCase.php`, `tests/Fakes/FakeAiProvider.php`, `tests/stubs/User.php`.
- Create `tests/Unit/GeminiProviderTest.php`, `tests/Feature/AeonServiceTest.php`, `tests/Feature/AeonChatEndpointTest.php`.
- **Delete** the old scaffold (Task 2 lists exact files).

**`packages/aero-ui`** (frontend):
- Create `resources/js/aeon/aeonClient.js`, `resources/js/aeon/useAeon.js`, `resources/js/aeon/BlockRenderer.jsx`, `resources/js/aeon/AeonDrawer.jsx`, `resources/js/aeon/FloatingAeonButton.jsx`, `resources/js/aeon/FloatingAeon.jsx`.
- Create `resources/js/Pages/Aeon/Index.jsx`.
- Modify `resources/js/app.jsx` — mount `<FloatingAeon/>` globally.
- Modify `resources/js/index.js` — export the Aeon components.

**Host apps** (`../aeos365`, `../aeos365-standalone`):
- Modify each `composer.json` — add `"aero/assistant": "@dev"`.
- Modify each `.env` — add `AEON_*` / `GEMINI_*` keys.

---

## Task 1: `AiProvider` contract + `AiChatResult` DTO (aero-contracts)

**Files:**
- Create: `packages/aero-contracts/src/Ai/AiProvider.php`
- Create: `packages/aero-contracts/src/Ai/AiChatResult.php`
- Test: `packages/aero-contracts/tests/Unit/Ai/AiChatResultTest.php`

**Interfaces:**
- Produces: `Aero\Contracts\Ai\AiProvider` with
  `chat(array $messages, array $tools = [], array $options = []): AiChatResult`,
  `embed(array $texts, array $options = []): array`,
  `isAvailable(): bool`.
- Produces: `Aero\Contracts\Ai\AiChatResult` — readonly, props `content:string`, `toolCalls:array`, `tokensUsed:int`, `model:string`, `success:bool`, `error:?string`; static `failed(string $error, string $model = ''): self`.
- Canonical message shape used everywhere: `['role' => 'system'|'user'|'assistant', 'content' => string]`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/aero-contracts/tests/Unit/Ai/AiChatResultTest.php
declare(strict_types=1);

namespace Aero\Contracts\Tests\Unit\Ai;

use Aero\Contracts\Ai\AiChatResult;
use PHPUnit\Framework\TestCase;

class AiChatResultTest extends TestCase
{
    public function test_holds_a_successful_reply(): void
    {
        $r = new AiChatResult(content: 'hi', tokensUsed: 7, model: 'gemini-flash-latest');
        $this->assertTrue($r->success);
        $this->assertSame('hi', $r->content);
        $this->assertSame(7, $r->tokensUsed);
        $this->assertNull($r->error);
        $this->assertSame([], $r->toolCalls);
    }

    public function test_failed_factory_marks_unsuccessful(): void
    {
        $r = AiChatResult::failed('timeout', 'gemini-flash-latest');
        $this->assertFalse($r->success);
        $this->assertSame('timeout', $r->error);
        $this->assertSame('', $r->content);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd packages/aero-contracts && php ../../../aeos365/vendor/bin/phpunit tests/Unit/Ai/AiChatResultTest.php`
Expected: FAIL — class `Aero\Contracts\Ai\AiChatResult` not found.
(If the package has no `phpunit.xml`, mirror `packages/aero-auth/phpunit.xml`; contracts tests already exist under `packages/aero-contracts/tests`, so a runner is present.)

- [ ] **Step 3: Write the DTO and interface**

```php
<?php // packages/aero-contracts/src/Ai/AiChatResult.php
declare(strict_types=1);

namespace Aero\Contracts\Ai;

/** Immutable result of one AI chat turn (provider-agnostic). */
final class AiChatResult
{
    public function __construct(
        public readonly string $content,
        public readonly array $toolCalls = [],
        public readonly int $tokensUsed = 0,
        public readonly string $model = '',
        public readonly bool $success = true,
        public readonly ?string $error = null,
    ) {}

    public static function failed(string $error, string $model = ''): self
    {
        return new self(content: '', toolCalls: [], tokensUsed: 0, model: $model, success: false, error: $error);
    }
}
```

```php
<?php // packages/aero-contracts/src/Ai/AiProvider.php
declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * Provider-agnostic AI model interface. Feature code depends only on this.
 * Drivers (Gemini, OpenAI, Anthropic, OpenAI-compatible) live in aero-assistant.
 *
 * @param array<int,array{role:string,content:string}> $messages
 */
interface AiProvider
{
    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult;

    /** @param array<int,string> $texts  @return array<int,array<int,float>> one vector per text */
    public function embed(array $texts, array $options = []): array;

    public function isAvailable(): bool;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd packages/aero-contracts && php ../../../aeos365/vendor/bin/phpunit tests/Unit/Ai/AiChatResultTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-contracts/src/Ai packages/aero-contracts/tests/Unit/Ai
git commit -m "feat(contracts): AiProvider interface + AiChatResult DTO (Aeon model layer)"
```

---

## Task 2: Reset the aero-assistant package skeleton (composer, config, provider, test harness)

Rebuild the package around **Aeon** and delete the pgvector/self-hosted scaffold. Deliverable: `AeonServiceProvider` boots in an isolated testbench and reads `config('aeon.provider')`.

**Files:**
- Modify: `packages/aero-assistant/composer.json`
- Create: `packages/aero-assistant/config/aeon.php`
- Replace: `packages/aero-assistant/config/module.php`
- Create: `packages/aero-assistant/src/Providers/AeonServiceProvider.php`
- Create: `packages/aero-assistant/phpunit.xml`
- Create: `packages/aero-assistant/tests/bootstrap.php`
- Create: `packages/aero-assistant/tests/PackageTestCase.php`
- Create: `packages/aero-assistant/tests/Fakes/FakeAiProvider.php`
- Test: `packages/aero-assistant/tests/Feature/ProviderBootTest.php`
- **Delete:** `src/Services/AiModelService.php`, `src/Services/RagService.php`, `src/Services/IndexingService.php`, `src/Services/AssistantService.php`, `src/Models/Embedding.php`, `src/Models/UsageLog.php`, `src/Models/Conversation.php`, `src/Models/Message.php`, `src/Http/Controllers/AssistantController.php`, `src/Http/Controllers/AssistantPageController.php`, `src/Providers/AssistantModuleProvider.php`, `src/Console/Commands/IndexKnowledgeBase.php`, `src/Console/Commands/AssistantStats.php`, `config/assistant.php`, `routes/api.php`, `routes/tenant.php`, `routes/web.php`, `resources/js/` (whole dir), `database/migrations/2024_01_01_0000{01,02,03,04}_*.php`, `module.json`, `setup.sh`, `.env.example`, `ai-server/` (whole dir), `training/` (whole dir), `QUICKSTART.md`, `DEPLOYMENT.md`. (Models/Conversation.php + Message.php are recreated in Task 3; migrations in Task 3.)

**Interfaces:**
- Consumes: `Aero\Contracts\Ai\AiProvider` (Task 1).
- Produces: `Aero\Assistant\Providers\AeonServiceProvider` (moduleCode `aeon`); container binding `AiProvider::class` → configured driver; `Aero\Assistant\Tests\PackageTestCase`; `Aero\Assistant\Tests\Fakes\FakeAiProvider`.

- [ ] **Step 1: Delete the old scaffold**

```bash
cd packages/aero-assistant
rm -rf ai-server training resources/js src/Services src/Console \
  src/Models/Embedding.php src/Models/UsageLog.php src/Models/Conversation.php src/Models/Message.php \
  src/Http/Controllers/AssistantController.php src/Http/Controllers/AssistantPageController.php \
  src/Providers/AssistantModuleProvider.php config/assistant.php \
  routes/api.php routes/tenant.php routes/web.php module.json setup.sh .env.example \
  QUICKSTART.md DEPLOYMENT.md \
  database/migrations/2024_01_01_000001_create_assistant_conversations_table.php \
  database/migrations/2024_01_01_000002_create_assistant_messages_table.php \
  database/migrations/2024_01_01_000003_create_assistant_embeddings_table.php \
  database/migrations/2024_01_01_000004_create_assistant_usage_logs_table.php
cd ../..
```

- [ ] **Step 2: Rewrite `composer.json`**

```json
{
    "name": "aero/assistant",
    "description": "Aeon — AEOS365 built-in AI assistant (provider-agnostic; Gemini default)",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "aero/core": "@dev",
        "aero/contracts": "@dev"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "orchestra/testbench": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "Aero\\Assistant\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Aero\\Assistant\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Aero\\Assistant\\Providers\\AeonServiceProvider"
            ]
        },
        "aero": {
            "package": "assistant",
            "version": "1.0.0",
            "category": "product",
            "tier": "product"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": { "sort-packages": true }
}
```

(Confirm `orchestra/testbench` major matches the host's installed version — check `../aeos365/composer.lock`. Use the same major the other packages' `require-dev` use; `packages/aero-auth/composer.json` shows the working version.)

- [ ] **Step 3: Create `config/aeon.php`**

```php
<?php // packages/aero-assistant/config/aeon.php
return [
    'enabled'  => env('AEON_ENABLED', true),
    'provider' => env('AEON_PROVIDER', 'gemini'),

    'system_prompt' => env('AEON_SYSTEM_PROMPT',
        'You are Aeon, the built-in AI assistant for AEOS365, an enterprise ERP suite. '
        .'Be concise, accurate, and helpful. If you are unsure, say so.'),

    'providers' => [
        'gemini' => [
            'api_key'   => env('GEMINI_API_KEY'),
            'model'     => env('GEMINI_MODEL', 'gemini-flash-latest'),
            'endpoint'  => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout'   => (int) env('GEMINI_TIMEOUT', 30),
            'embed_model' => env('GEMINI_EMBED_MODEL', 'gemini-embedding-001'),
            'embed_dims'  => (int) env('GEMINI_EMBED_DIMS', 768),
        ],
    ],

    'ui' => [
        'floating_button' => env('AEON_FLOATING_BUTTON', true),
    ],
];
```

- [ ] **Step 4: Replace `config/module.php` with a lean Aeon module**

```php
<?php // packages/aero-assistant/config/module.php
return [
    'code' => 'aeon',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Aeon',
    'description' => 'AEOS365 built-in AI assistant.',
    'version' => '1.0.0',
    'icon' => 'SparklesIcon',
    'category' => 'productivity',
    'priority' => 100,
    'is_core' => false,
    'is_active' => true,
    'enabled' => true,
    'route_prefix' => 'aeon',
    'min_plan' => null,
    'dependencies' => ['core'],
    'submodules' => [
        [
            'code' => 'chat',
            'name' => 'Aeon',
            'description' => 'Chat with the Aeon assistant',
            'icon' => 'SparklesIcon',
            'route' => 'aeon.index',
            'priority' => 1,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'chat_interface',
                    'name' => 'Aeon Chat',
                    'route_name' => 'aeon.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'use', 'name' => 'Use Aeon', 'is_active' => true],
                        ['code' => 'view_history', 'name' => 'View History', 'is_active' => true],
                    ],
                ],
            ],
        ],
    ],
];
```

- [ ] **Step 5: Create `AeonServiceProvider`**

```php
<?php // packages/aero-assistant/src/Providers/AeonServiceProvider.php
declare(strict_types=1);

namespace Aero\Assistant\Providers;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Services\AeonService;
use Aero\Contracts\Ai\AiProvider;
use Aero\Contracts\Providers\AbstractModuleProvider;

class AeonServiceProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'aeon';

    protected function getModulePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base.'/'.$path : $base;
    }

    protected function registerServices(): void
    {
        $this->mergeConfigFrom($this->getModulePath('config/aeon.php'), 'aeon');

        $this->app->singleton(AiProvider::class, function () {
            return match (config('aeon.provider', 'gemini')) {
                'gemini' => new GeminiProvider(),
                default  => new GeminiProvider(),
            };
        });

        $this->app->singleton(AeonService::class);
    }
}
```

- [ ] **Step 6: Create the test harness** (`phpunit.xml`, `tests/bootstrap.php`, `PackageTestCase`, `FakeAiProvider`)

```xml
<!-- packages/aero-assistant/phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="../../vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
    </testsuites>
    <source><include><directory>src</directory></include></source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10="/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="GEMINI_API_KEY" value="test-key"/>
    </php>
</phpunit>
```

```php
<?php // packages/aero-assistant/tests/bootstrap.php
// Mirrors packages/aero-auth/tests/bootstrap.php.
$vendorAutoload = __DIR__.'/../../../aeos365/vendor/autoload.php';
if (! file_exists($vendorAutoload)) { $vendorAutoload = __DIR__.'/../../../vendor/autoload.php'; }
if (! file_exists($vendorAutoload)) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install in the host app first.\n");
    exit(1);
}
$loader = require $vendorAutoload;
$loader->addPsr4('Aero\\Assistant\\Tests\\', __DIR__.'/');
```

```php
<?php // packages/aero-assistant/tests/PackageTestCase.php
declare(strict_types=1);

namespace Aero\Assistant\Tests;

use Aero\Assistant\Providers\AeonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeonServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqlite);
        $app['config']->set('database.connections.central', $sqlite);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('aeon.provider', 'gemini');
        $app['config']->set('aeon.providers.gemini.api_key', 'test-key');
        $app['config']->set('aeon.providers.gemini.model', 'gemini-flash-latest');
        $app['config']->set('aeon.providers.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $app['config']->set('aeon.providers.gemini.timeout', 30);
    }
}
```

```php
<?php // packages/aero-assistant/tests/Fakes/FakeAiProvider.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Fakes;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;

class FakeAiProvider implements AiProvider
{
    public array $received = [];

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $this->received = $messages;
        return new AiChatResult(content: 'Hello from Aeon test', tokensUsed: 5, model: 'fake');
    }

    public function embed(array $texts, array $options = []): array
    {
        return array_map(fn () => array_fill(0, 768, 0.0), $texts);
    }

    public function isAvailable(): bool { return true; }
}
```

- [ ] **Step 7: Write the failing boot test**

```php
<?php // packages/aero-assistant/tests/Feature/ProviderBootTest.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiProvider;

class ProviderBootTest extends PackageTestCase
{
    public function test_container_resolves_configured_ai_provider(): void
    {
        $provider = $this->app->make(AiProvider::class);
        $this->assertInstanceOf(GeminiProvider::class, $provider);
    }
}
```

- [ ] **Step 8: Run it — fails (GeminiProvider missing)**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/ProviderBootTest.php`
Expected: FAIL — class `Aero\Assistant\Providers\Models\GeminiProvider` not found. (This is expected; GeminiProvider is built in Task 4. To make Task 2 self-contained, temporarily create a stub in the next step.)

- [ ] **Step 9: Create a minimal `GeminiProvider` stub so the provider binding resolves**

```php
<?php // packages/aero-assistant/src/Providers/Models/GeminiProvider.php
declare(strict_types=1);

namespace Aero\Assistant\Providers\Models;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;

class GeminiProvider implements AiProvider
{
    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        return AiChatResult::failed('not implemented', config('aeon.providers.gemini.model', ''));
    }

    public function embed(array $texts, array $options = []): array { return []; }

    public function isAvailable(): bool { return false; }
}
```

- [ ] **Step 10: Run — passes**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/ProviderBootTest.php`
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add packages/aero-assistant
git commit -m "refactor(aeon): reset package to provider-agnostic Aeon skeleton + testbench harness

Drops pgvector/self-hosted-model scaffold; adds AeonServiceProvider (code 'aeon'),
config/aeon.php, lean module.php, GeminiProvider stub, PackageTestCase + FakeAiProvider."
```

---

## Task 3: Conversation + Message models & migrations

**Files:**
- Create: `packages/aero-assistant/database/migrations/2026_07_07_000001_create_aeon_conversations_table.php`
- Create: `packages/aero-assistant/database/migrations/2026_07_07_000002_create_aeon_messages_table.php`
- Create: `packages/aero-assistant/src/Models/Conversation.php`
- Create: `packages/aero-assistant/src/Models/Message.php`
- Test: `packages/aero-assistant/tests/Feature/ConversationModelTest.php`

**Interfaces:**
- Produces: `Aero\Assistant\Models\Conversation` (fillable `user_id,title,context,archived_at`; casts `context=>array`, `archived_at=>datetime`; `messages(): HasMany`).
- Produces: `Aero\Assistant\Models\Message` (fillable `conversation_id,role,content,blocks,tool_calls,tokens,provider,model`; casts `blocks=>array`, `tool_calls=>array`; `conversation(): BelongsTo`). Table `aeon_messages`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/aero-assistant/tests/Feature/ConversationModelTest.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Tests\PackageTestCase;

class ConversationModelTest extends PackageTestCase
{
    public function test_conversation_has_messages_with_json_blocks(): void
    {
        $c = Conversation::create(['user_id' => 1, 'title' => 'First chat']);
        $c->messages()->create([
            'role' => 'assistant',
            'content' => 'hi',
            'blocks' => [['type' => 'text', 'text' => 'hi']],
            'tokens' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-flash-latest',
        ]);

        $c->refresh();
        $this->assertCount(1, $c->messages);
        $this->assertSame('text', $c->messages->first()->blocks[0]['type']);
        $this->assertSame(1, $c->user_id);
    }
}
```

- [ ] **Step 2: Run — fails**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/ConversationModelTest.php`
Expected: FAIL — model/table missing.

- [ ] **Step 3: Create migrations**

```php
<?php // packages/aero-assistant/database/migrations/2026_07_07_000001_create_aeon_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aeon_conversations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('title')->nullable();
            $t->json('context')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('aeon_conversations'); }
};
```

```php
<?php // packages/aero-assistant/database/migrations/2026_07_07_000002_create_aeon_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aeon_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('aeon_conversations')->cascadeOnDelete();
            $t->string('role'); // user | assistant | tool
            $t->longText('content')->nullable();
            $t->json('blocks')->nullable();
            $t->json('tool_calls')->nullable();
            $t->unsignedInteger('tokens')->default(0);
            $t->string('provider')->nullable();
            $t->string('model')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('aeon_messages'); }
};
```

- [ ] **Step 4: Create models**

```php
<?php // packages/aero-assistant/src/Models/Conversation.php
declare(strict_types=1);

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends TenantModel
{
    protected $table = 'aeon_conversations';

    protected $fillable = ['user_id', 'title', 'context', 'archived_at'];

    protected $casts = ['context' => 'array', 'archived_at' => 'datetime'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
```

```php
<?php // packages/aero-assistant/src/Models/Message.php
declare(strict_types=1);

namespace Aero\Assistant\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends TenantModel
{
    protected $table = 'aeon_messages';

    protected $fillable = ['conversation_id', 'role', 'content', 'blocks', 'tool_calls', 'tokens', 'provider', 'model'];

    protected $casts = ['blocks' => 'array', 'tool_calls' => 'array', 'tokens' => 'integer'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
```

(If `TenantModel` forces a non-default connection that the testbench sqlite setup doesn't map, the `PackageTestCase` already maps `central` + `testing` to the in-memory DB. If a test still can't find the table, confirm `TenantModel::$connection` — mirror how `packages/aero-hrm` models behave in their passing tests.)

- [ ] **Step 5: Run — passes**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/ConversationModelTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/aero-assistant/database/migrations packages/aero-assistant/src/Models packages/aero-assistant/tests/Feature/ConversationModelTest.php
git commit -m "feat(aeon): aeon_conversations + aeon_messages tables and models (blocks as JSON)"
```

---

## Task 4: `GeminiProvider` (real Http implementation)

**Files:**
- Modify: `packages/aero-assistant/src/Providers/Models/GeminiProvider.php` (replace the Task 2 stub)
- Test: `packages/aero-assistant/tests/Unit/GeminiProviderTest.php`

**Interfaces:**
- Consumes: `config('aeon.providers.gemini.*')`.
- Produces: `GeminiProvider::chat()` maps canonical messages → Gemini `generateContent`, returns `AiChatResult`; `embed()` calls `:embedContent`; `isAvailable()` GETs `/models`.
- Gemini mapping: `system` message → `systemInstruction.parts[].text`; `user`→role `user`, `assistant`→role `model`; each content = `['role'=>..,'parts'=>[['text'=>..]]]`.

- [ ] **Step 1: Write the failing unit test**

```php
<?php // packages/aero-assistant/tests/Unit/GeminiProviderTest.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Unit;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Illuminate\Support\Facades\Http;

class GeminiProviderTest extends PackageTestCase
{
    public function test_chat_maps_messages_and_parses_reply(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hi there']]]]],
                'usageMetadata' => ['totalTokenCount' => 12],
            ], 200),
        ]);

        $result = (new GeminiProvider())->chat([
            ['role' => 'system', 'content' => 'You are Aeon.'],
            ['role' => 'user', 'content' => 'hello'],
        ]);

        $this->assertTrue($result->success);
        $this->assertSame('Hi there', $result->content);
        $this->assertSame(12, $result->tokensUsed);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), ':generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $body['systemInstruction']['parts'][0]['text'] === 'You are Aeon.'
                && $body['contents'][0]['role'] === 'user'
                && $body['contents'][0]['parts'][0]['text'] === 'hello';
        });
    }

    public function test_chat_returns_failed_result_on_http_error(): void
    {
        Http::fake(['*generativelanguage*' => Http::response(['error' => 'nope'], 429)]);
        $result = (new GeminiProvider())->chat([['role' => 'user', 'content' => 'x']]);
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }
}
```

- [ ] **Step 2: Run — fails** (stub returns "not implemented")

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Unit/GeminiProviderTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `GeminiProvider`**

```php
<?php // packages/aero-assistant/src/Providers/Models/GeminiProvider.php
declare(strict_types=1);

namespace Aero\Assistant\Providers\Models;

use Aero\Contracts\Ai\AiChatResult;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AiProvider
{
    private string $key;
    private string $model;
    private string $endpoint;
    private int $timeout;

    public function __construct()
    {
        $cfg = config('aeon.providers.gemini');
        $this->key = (string) ($cfg['api_key'] ?? '');
        $this->model = (string) ($cfg['model'] ?? 'gemini-flash-latest');
        $this->endpoint = rtrim((string) ($cfg['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->timeout = (int) ($cfg['timeout'] ?? 30);
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiChatResult
    {
        $system = null;
        $contents = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'system') {
                $system = $m['content'] ?? '';
                continue;
            }
            $contents[] = [
                'role'  => ($m['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($m['content'] ?? '')]],
            ];
        }

        $payload = ['contents' => $contents];
        if ($system) {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        try {
            $res = Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout($this->timeout)
                ->post("{$this->endpoint}/models/{$this->model}:generateContent", $payload);

            if ($res->failed()) {
                return AiChatResult::failed('Gemini HTTP '.$res->status(), $this->model);
            }

            $json = $res->json();
            $text = data_get($json, 'candidates.0.content.parts.0.text', '');
            $tokens = (int) data_get($json, 'usageMetadata.totalTokenCount', 0);

            return new AiChatResult(content: (string) $text, tokensUsed: $tokens, model: $this->model);
        } catch (\Throwable $e) {
            Log::error('Aeon GeminiProvider chat failed', ['error' => $e->getMessage()]);
            return AiChatResult::failed($e->getMessage(), $this->model);
        }
    }

    public function embed(array $texts, array $options = []): array
    {
        $cfg = config('aeon.providers.gemini');
        $embedModel = (string) ($cfg['embed_model'] ?? 'gemini-embedding-001');
        $dims = (int) ($cfg['embed_dims'] ?? 768);
        $out = [];
        foreach ($texts as $text) {
            $res = Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout($this->timeout)
                ->post("{$this->endpoint}/models/{$embedModel}:embedContent", [
                    'model' => "models/{$embedModel}",
                    'content' => ['parts' => [['text' => $text]]],
                    'outputDimensionality' => $dims,
                ]);
            $out[] = (array) data_get($res->json(), 'embedding.values', []);
        }
        return $out;
    }

    public function isAvailable(): bool
    {
        try {
            return Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout(5)->get("{$this->endpoint}/models")->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
```

- [ ] **Step 4: Run — passes**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Unit/GeminiProviderTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-assistant/src/Providers/Models/GeminiProvider.php packages/aero-assistant/tests/Unit/GeminiProviderTest.php
git commit -m "feat(aeon): real GeminiProvider (generateContent + embedContent via Http, mapped + tested)"
```

---

## Task 5: `AeonService` — one chat turn

**Files:**
- Create: `packages/aero-assistant/src/Services/AeonService.php`
- Test: `packages/aero-assistant/tests/Feature/AeonServiceTest.php`

**Interfaces:**
- Consumes: `AiProvider` (constructor-injected), `Conversation`, `Message`, `config('aeon.system_prompt')`.
- Produces: `AeonService::send(int $userId, ?int $conversationId, string $text): array` returning `['conversation' => Conversation, 'reply' => Message]`. Creates/loads the conversation (scoped to `$userId`), persists the user message, builds canonical history (system + prior turns), calls `provider->chat()`, persists the assistant message with a text block. All inside `DB::transaction()`.

- [ ] **Step 1: Write the failing test**

```php
<?php // packages/aero-assistant/tests/Feature/AeonServiceTest.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Tests\Fakes\FakeAiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiProvider;

class AeonServiceTest extends PackageTestCase
{
    public function test_send_persists_user_and_assistant_messages(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $out = $this->app->make(AeonService::class)->send(1, null, 'How do I add an employee?');

        $this->assertInstanceOf(Conversation::class, $out['conversation']);
        $this->assertInstanceOf(Message::class, $out['reply']);
        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Message::count()); // user + assistant
        $this->assertSame('Hello from Aeon test', $out['reply']->content);
        $this->assertSame('text', $out['reply']->blocks[0]['type']);
        $this->assertSame('assistant', $out['reply']->role);
        $this->assertSame('fake', $out['reply']->model);
    }

    public function test_send_reuses_existing_conversation_and_sends_history(): void
    {
        $fake = new FakeAiProvider();
        $this->app->instance(AiProvider::class, $fake);
        $svc = $this->app->make(AeonService::class);

        $first = $svc->send(1, null, 'hi');
        $svc->send(1, $first['conversation']->id, 'again');

        $this->assertSame(1, Conversation::count());
        $this->assertSame(4, Message::count());
        // history passed to provider begins with the system prompt
        $this->assertSame('system', $fake->received[0]['role']);
    }
}
```

- [ ] **Step 2: Run — fails** (AeonService missing)

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/AeonServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `AeonService`**

```php
<?php // packages/aero-assistant/src/Services/AeonService.php
declare(strict_types=1);

namespace Aero\Assistant\Services;

use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Contracts\Ai\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AeonService
{
    public function __construct(private AiProvider $provider) {}

    /** @return array{conversation: Conversation, reply: Message} */
    public function send(int $userId, ?int $conversationId, string $text): array
    {
        return DB::transaction(function () use ($userId, $conversationId, $text) {
            $conversation = $conversationId
                ? Conversation::where('user_id', $userId)->findOrFail($conversationId)
                : Conversation::create(['user_id' => $userId, 'title' => Str::limit($text, 40)]);

            $conversation->messages()->create(['role' => 'user', 'content' => $text]);

            $result = $this->provider->chat($this->buildHistory($conversation));

            $content = $result->success
                ? $result->content
                : 'Sorry — Aeon is temporarily unavailable. Please try again.';

            $reply = $conversation->messages()->create([
                'role'     => 'assistant',
                'content'  => $content,
                'blocks'   => [['type' => 'text', 'text' => $content]],
                'tokens'   => $result->tokensUsed,
                'provider' => config('aeon.provider'),
                'model'    => $result->model,
            ]);

            return ['conversation' => $conversation, 'reply' => $reply];
        });
    }

    /** @return array<int,array{role:string,content:string}> */
    private function buildHistory(Conversation $conversation): array
    {
        $messages = [[
            'role' => 'system',
            'content' => (string) config('aeon.system_prompt'),
        ]];

        foreach ($conversation->messages()->orderBy('id')->get() as $m) {
            $messages[] = ['role' => $m->role, 'content' => (string) $m->content];
        }

        return $messages;
    }
}
```

- [ ] **Step 4: Run — passes**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/AeonServiceTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-assistant/src/Services/AeonService.php packages/aero-assistant/tests/Feature/AeonServiceTest.php
git commit -m "feat(aeon): AeonService chat turn (persist user+assistant, history, transactional)"
```

---

## Task 6: HTTP layer — request, controllers, routes

**Files:**
- Create: `packages/aero-assistant/src/Http/Requests/SendMessageRequest.php`
- Create: `packages/aero-assistant/src/Http/Controllers/AeonController.php`
- Create: `packages/aero-assistant/src/Http/Controllers/AeonPageController.php`
- Create: `packages/aero-assistant/routes/web.php`
- Create: `packages/aero-assistant/tests/stubs/User.php`
- Test: `packages/aero-assistant/tests/Feature/AeonChatEndpointTest.php`

**Interfaces:**
- Produces routes (auto-loaded by `AbstractModuleProvider::loadRoutes()` with prefix `aeon`, name prefix `aeon.`):
  - `GET  /aeon` → `AeonPageController@index` (`aeon.index`) — Inertia `Aeon/Index`.
  - `POST /aeon/message` → `AeonController@message` (`aeon.message`) — JSON.
  - `GET  /aeon/conversations` → `AeonController@conversations` (`aeon.conversations`) — JSON.
  - `GET  /aeon/conversations/{conversation}` → `AeonController@show` (`aeon.conversation`) — JSON.
- `message` JSON response shape (consumed by the frontend in Task 7):
  `{ "conversation_id": int, "reply": { "role": "assistant", "content": string, "blocks": [{ "type":"text","text":string }] } }`.

- [ ] **Step 1: Write the failing endpoint test**

```php
<?php // packages/aero-assistant/tests/Feature/AeonChatEndpointTest.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Models\Message;
use Aero\Assistant\Tests\Fakes\FakeAiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Assistant\Tests\Stubs\User;
use Aero\Contracts\Ai\AiProvider;

class AeonChatEndpointTest extends PackageTestCase
{
    public function test_post_message_returns_reply_blocks_and_persists(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());

        $response = $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson('/aeon/message', ['message' => 'Hello Aeon']);

        $response->assertOk()
            ->assertJsonPath('reply.role', 'assistant')
            ->assertJsonPath('reply.blocks.0.type', 'text')
            ->assertJsonPath('reply.blocks.0.text', 'Hello from Aeon test');

        $this->assertSame(2, Message::count());
    }

    public function test_message_is_required(): void
    {
        $this->app->instance(AiProvider::class, new FakeAiProvider());
        $this->actingAs(new User(['id' => 7]))
            ->withoutMiddleware()
            ->postJson('/aeon/message', ['message' => ''])
            ->assertStatus(422);
    }
}
```

```php
<?php // packages/aero-assistant/tests/stubs/User.php
declare(strict_types=1);

namespace Aero\Assistant\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
    // id is set via attributes; getAuthIdentifier() returns it for auth()->id().
}
```

Note: `withoutMiddleware()` bypasses `web`/`auth`/tenant middleware so the test targets controller+service+routing wiring. `auth()->id()` resolves from `actingAs`.

- [ ] **Step 2: Run — fails** (routes/controllers missing)

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/AeonChatEndpointTest.php`
Expected: FAIL — 404 / class not found.

- [ ] **Step 3: Create the Form Request**

```php
<?php // packages/aero-assistant/src/Http/Requests/SendMessageRequest.php
declare(strict_types=1);

namespace Aero\Assistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }
}
```

- [ ] **Step 4: Create the controllers**

```php
<?php // packages/aero-assistant/src/Http/Controllers/AeonController.php
declare(strict_types=1);

namespace Aero\Assistant\Http\Controllers;

use Aero\Assistant\Http\Requests\SendMessageRequest;
use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Services\AeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AeonController extends Controller
{
    public function __construct(private AeonService $aeon) {}

    public function message(SendMessageRequest $request): JsonResponse
    {
        $out = $this->aeon->send(
            (int) auth()->id(),
            $request->integer('conversation_id') ?: null,
            (string) $request->string('message'),
        );

        $reply = $out['reply'];

        return response()->json([
            'conversation_id' => $out['conversation']->id,
            'reply' => [
                'role' => $reply->role,
                'content' => $reply->content,
                'blocks' => $reply->blocks ?? [['type' => 'text', 'text' => $reply->content]],
            ],
        ]);
    }

    public function conversations(): JsonResponse
    {
        $items = Conversation::where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return response()->json(['conversations' => $items]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403);

        return response()->json([
            'conversation' => $conversation->only(['id', 'title']),
            'messages' => $conversation->messages()->orderBy('id')
                ->get(['id', 'role', 'content', 'blocks']),
        ]);
    }
}
```

```php
<?php // packages/aero-assistant/src/Http/Controllers/AeonPageController.php
declare(strict_types=1);

namespace Aero\Assistant\Http\Controllers;

use Aero\Assistant\Models\Conversation;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AeonPageController extends Controller
{
    public function index(): Response
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('Aeon/Index', [
            'conversations' => $conversations,
        ]);
    }
}
```

- [ ] **Step 5: Create routes**

```php
<?php // packages/aero-assistant/routes/web.php
use Aero\Assistant\Http\Controllers\AeonController;
use Aero\Assistant\Http\Controllers\AeonPageController;
use Illuminate\Support\Facades\Route;

// Prefix 'aeon' + name 'aeon.' are applied by AbstractModuleProvider::loadRoutes().
Route::middleware('auth')->group(function () {
    Route::get('/', [AeonPageController::class, 'index'])->name('index');
    Route::post('/message', [AeonController::class, 'message'])->name('message');
    Route::get('/conversations', [AeonController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{conversation}', [AeonController::class, 'show'])->name('conversation');
});
```

- [ ] **Step 6: Run — passes**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit tests/Feature/AeonChatEndpointTest.php`
Expected: PASS (2 tests). If routes don't register in testbench because `loadRoutes()` needs `routes/web.php` to exist (it now does), confirm the provider booted. Run the whole suite next.

- [ ] **Step 7: Run the full package suite**

Run: `cd packages/aero-assistant && php ../../../aeos365/vendor/bin/phpunit`
Expected: PASS — ProviderBoot, ConversationModel, GeminiProvider (2), AeonService (2), AeonChatEndpoint (2).

- [ ] **Step 8: Commit**

```bash
git add packages/aero-assistant/src/Http packages/aero-assistant/routes packages/aero-assistant/tests
git commit -m "feat(aeon): HTTP layer — /aeon page + POST /aeon/message JSON endpoint (auth-gated, tested)"
```

---

## Task 7: Frontend — drawer, button, page, block renderer, hook (aero-ui)

**Files:**
- Create: `packages/aero-ui/resources/js/aeon/aeonClient.js`
- Create: `packages/aero-ui/resources/js/aeon/useAeon.js`
- Create: `packages/aero-ui/resources/js/aeon/BlockRenderer.jsx`
- Create: `packages/aero-ui/resources/js/aeon/AeonDrawer.jsx`
- Create: `packages/aero-ui/resources/js/aeon/FloatingAeonButton.jsx`
- Create: `packages/aero-ui/resources/js/aeon/FloatingAeon.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Aeon/Index.jsx`
- Modify: `packages/aero-ui/resources/js/app.jsx` (mount `<FloatingAeon/>`)
- Modify: `packages/aero-ui/resources/js/index.js` (export Aeon components)

**Interfaces:**
- Consumes: `POST /aeon/message` (Task 6 shape). Available `@aero/ui` exports (verified): `Drawer, Button, IconButton, Card, Stack, HStack, VStack, Text, Heading, Input, Textarea, Icon, EmptyState`, `usePage` (from `@inertiajs/react`).
- Produces: `useAeon()` hook — `{ messages, open, isOpen, close, send, sending }`; `BlockRenderer` (renders `[{type:'text',text}]`, unknown → text fallback); `FloatingAeon` (button + drawer, hidden when unauthenticated).

- [ ] **Step 1: API client** (CSRF via Laravel's `XSRF-TOKEN` cookie)

```js
// packages/aero-ui/resources/js/aeon/aeonClient.js
function xsrf() {
  const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : '';
}

export async function sendAeonMessage({ message, conversationId }) {
  const res = await fetch('/aeon/message', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf(),
    },
    body: JSON.stringify({ message, conversation_id: conversationId ?? null }),
  });
  if (!res.ok) throw new Error(`Aeon request failed (${res.status})`);
  return res.json(); // { conversation_id, reply: { role, content, blocks } }
}
```

- [ ] **Step 2: Hook**

```js
// packages/aero-ui/resources/js/aeon/useAeon.js
import { useCallback, useState } from 'react';
import { sendAeonMessage } from './aeonClient.js';

export function useAeon() {
  const [messages, setMessages] = useState([]);
  const [conversationId, setConversationId] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [sending, setSending] = useState(false);

  const open = useCallback(() => setIsOpen(true), []);
  const close = useCallback(() => setIsOpen(false), []);

  const send = useCallback(async (text) => {
    const trimmed = (text || '').trim();
    if (!trimmed || sending) return;
    setMessages((m) => [...m, { role: 'user', blocks: [{ type: 'text', text: trimmed }] }]);
    setSending(true);
    try {
      const data = await sendAeonMessage({ message: trimmed, conversationId });
      setConversationId(data.conversation_id);
      setMessages((m) => [...m, { role: 'assistant', blocks: data.reply.blocks }]);
    } catch (e) {
      setMessages((m) => [...m, { role: 'assistant', blocks: [{ type: 'text', text: 'Aeon is unavailable right now. Please try again.' }] }]);
    } finally {
      setSending(false);
    }
  }, [conversationId, sending]);

  return { messages, isOpen, open, close, send, sending };
}
```

- [ ] **Step 3: Block renderer** (M1: text only, unknown → text)

```jsx
// packages/aero-ui/resources/js/aeon/BlockRenderer.jsx
import React from 'react';
import { Text } from '@aero/ui';

export default function BlockRenderer({ blocks = [] }) {
  return (
    <>
      {blocks.map((block, i) => {
        switch (block.type) {
          case 'text':
          default:
            return <Text key={i}>{block.text ?? ''}</Text>;
        }
      })}
    </>
  );
}
```

- [ ] **Step 4: Drawer**

```jsx
// packages/aero-ui/resources/js/aeon/AeonDrawer.jsx
import React, { useState } from 'react';
import { Drawer, Stack, HStack, Card, Text, Input, Button, Heading, EmptyState } from '@aero/ui';
import BlockRenderer from './BlockRenderer.jsx';

export default function AeonDrawer({ isOpen, onClose, messages, sending, onSend }) {
  const [draft, setDraft] = useState('');

  const submit = (e) => {
    e.preventDefault();
    onSend(draft);
    setDraft('');
  };

  return (
    <Drawer open={isOpen} onClose={onClose} title="Aeon" side="right">
      <Stack gap="md">
        {messages.length === 0 ? (
          <EmptyState title="Ask Aeon anything" description="How do I add an employee? Where are billing settings?" />
        ) : (
          messages.map((m, i) => (
            <Card key={i} intent={m.role === 'user' ? 'muted' : 'default'}>
              <BlockRenderer blocks={m.blocks} />
            </Card>
          ))
        )}
        {sending && <Text muted>Aeon is thinking…</Text>}

        <form onSubmit={submit}>
          <HStack gap="sm">
            <Input
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              placeholder="Message Aeon…"
              disabled={sending}
            />
            <Button type="submit" disabled={sending || !draft.trim()}>Send</Button>
          </HStack>
        </form>
      </Stack>
    </Drawer>
  );
}
```

(If `Drawer`'s prop names differ — `isOpen` vs `open`, or `title` vs a header slot — check `packages/aero-ui/resources/js/components/Overlays.jsx` and adjust. Confirm `Card` accepts an `intent` prop; if not, drop it.)

- [ ] **Step 5: Floating button + composed FloatingAeon**

```jsx
// packages/aero-ui/resources/js/aeon/FloatingAeonButton.jsx
import React from 'react';
import { IconButton, Icon } from '@aero/ui';

export default function FloatingAeonButton({ onClick }) {
  return (
    <div className="aeon-fab">
      <IconButton aria-label="Ask Aeon" onClick={onClick}>
        <Icon name="Sparkles" />
      </IconButton>
    </div>
  );
}
```

```jsx
// packages/aero-ui/resources/js/aeon/FloatingAeon.jsx
import React from 'react';
import { usePage } from '@inertiajs/react';
import FloatingAeonButton from './FloatingAeonButton.jsx';
import AeonDrawer from './AeonDrawer.jsx';
import { useAeon } from './useAeon.js';

export default function FloatingAeon() {
  const page = usePage();
  const user = page?.props?.auth?.user;
  const aeon = useAeon();

  if (!user) return null; // only for authenticated users

  return (
    <>
      <FloatingAeonButton onClick={aeon.open} />
      <AeonDrawer
        isOpen={aeon.isOpen}
        onClose={aeon.close}
        messages={aeon.messages}
        sending={aeon.sending}
        onSend={aeon.send}
      />
    </>
  );
}
```

Add the FAB position style to `packages/aero-ui/resources/css/app.css` (no inline styles):
```css
/* Aeon floating action button */
.aeon-fab { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 60; }
```
(Confirm the icon key: open `packages/aero-ui/resources/js/icons/icons.jsx` and use the exact Sparkles key; if it's `SparklesIcon`, use that.)

- [ ] **Step 6: Mount globally in `app.jsx`**

In `packages/aero-ui/resources/js/app.jsx`, add the import and render `<FloatingAeon/>` inside `AeosEngine`:

```jsx
// add near the other imports
import FloatingAeon from './aeon/FloatingAeon.jsx';

// inside AeosEngine's return, after <ThemeDrawer />:
      <ThemeDrawer />
      <FloatingAeon />
    </ThemeProvider>
```

- [ ] **Step 7: Dedicated `/aeon` page**

```jsx
// packages/aero-ui/resources/js/Pages/Aeon/Index.jsx
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { DashboardLayout, Card, Stack, HStack, Input, Button, Heading, Text, EmptyState } from '@aero/ui';
import BlockRenderer from '../../aeon/BlockRenderer.jsx';
import { useAeon } from '../../aeon/useAeon.js';

export default function AeonPage() {
  const aeon = useAeon();
  const [draft, setDraft] = useState('');

  const submit = (e) => { e.preventDefault(); aeon.send(draft); setDraft(''); };

  return (
    <>
      <Head title="Aeon" />
      <Stack gap="lg">
        <Heading>Aeon</Heading>
        {aeon.messages.length === 0 ? (
          <EmptyState title="Ask Aeon anything" description="Your AEOS365 AI assistant." />
        ) : (
          aeon.messages.map((m, i) => (
            <Card key={i} intent={m.role === 'user' ? 'muted' : 'default'}>
              <BlockRenderer blocks={m.blocks} />
            </Card>
          ))
        )}
        {aeon.sending && <Text muted>Aeon is thinking…</Text>}
        <form onSubmit={submit}>
          <HStack gap="sm">
            <Input value={draft} onChange={(e) => setDraft(e.target.value)} placeholder="Message Aeon…" disabled={aeon.sending} />
            <Button type="submit" disabled={aeon.sending || !draft.trim()}>Send</Button>
          </HStack>
        </form>
      </Stack>
    </>
  );
}

AeonPage.layout = (page) => <DashboardLayout children={page} />;
```

(Confirm `DashboardLayout` usage matches other pages — check an existing page like `packages/aero-ui/resources/js/Pages/**` and mirror its `.layout` pattern exactly.)

- [ ] **Step 8: Export from the barrel**

In `packages/aero-ui/resources/js/index.js` add:
```js
// ── Aeon (AI assistant) ───────────────────────────────────────────
export { default as FloatingAeon }  from './aeon/FloatingAeon.jsx';
export { useAeon }                   from './aeon/useAeon.js';
export { default as AeonBlockRenderer } from './aeon/BlockRenderer.jsx';
```

- [ ] **Step 9: Build to verify it compiles**

Run (in a host app that builds aero-ui, e.g. `../aeos365`): `cd ../aeos365 && npm run build`
Expected: build succeeds, no unresolved imports from `@aero/ui` or `./aeon/*`. Fix any prop/export mismatches flagged (Drawer/Card/Icon).

- [ ] **Step 10: Commit**

```bash
git add packages/aero-ui/resources/js/aeon packages/aero-ui/resources/js/Pages/Aeon \
        packages/aero-ui/resources/js/app.jsx packages/aero-ui/resources/js/index.js \
        packages/aero-ui/resources/css/app.css
git commit -m "feat(aeon): frontend chat — FloatingAeon drawer + /aeon page + BlockRenderer (aero-ui)"
```

---

## Task 8: Host wiring + live smoke test

**Files:**
- Modify: `../aeos365/composer.json` (add `"aero/assistant": "@dev"`)
- Modify: `../aeos365-standalone/composer.json` (add `"aero/assistant": "@dev"`)
- Modify: `../aeos365/.env` and `../aeos365-standalone/.env` (add keys — NOT committed)

**Interfaces:** none produced; this makes Aeon live in both hosts.

- [ ] **Step 1: Require the package in both hosts**

In each host `composer.json` `require` block, add (alphabetically near the other `aero/*`):
```json
        "aero/assistant": "@dev",
```

- [ ] **Step 2: Install via the path repo (symlink)**

Run: `cd ../aeos365 && composer update aero/assistant --no-interaction`
Then: `cd ../aeos365-standalone && composer update aero/assistant --no-interaction`
Expected: `aero/assistant` symlinked from `packages/aero-assistant`; `AeonServiceProvider` auto-discovered.

- [ ] **Step 3: Add env keys** (each host `.env`; use the real key only in `aeos365`, a placeholder is fine in standalone until needed)

```env
AEON_ENABLED=true
AEON_PROVIDER=gemini
GEMINI_API_KEY=PASTE_THE_REAL_KEY_HERE
GEMINI_MODEL=gemini-flash-latest
```

- [ ] **Step 4: Migrate** (creates `aeon_conversations` + `aeon_messages`)

Run (SaaS uses tenant migrations; standalone uses default):
`cd ../aeos365-standalone && php artisan migrate`
`cd ../aeos365 && php artisan tenants:migrate` (or the project's tenant-migrate command — mirror how HRM migrations are applied for tenants).
Expected: both `aeon_*` tables created.

- [ ] **Step 5: Clear caches + build**

Run: `cd ../aeos365 && php artisan route:clear && php artisan config:clear && npm run build`

- [ ] **Step 6: Live smoke test** (real Gemini call)

1. Log into a host (standalone `http://aeos365-standalone.test` or a tenant).
2. Confirm the ✨ button appears bottom-right on an authenticated page.
3. Open it, send "Hello, who are you?" → expect a Gemini reply within a few seconds.
4. Visit `/aeon` → same chat renders full-page.
5. Verify persistence: `php artisan tinker` → `Aero\Assistant\Models\Message::count()` ≥ 2.
6. Confirm the network request to `POST /aeon/message` returns `200` with `reply.blocks`.

- [ ] **Step 7: Commit host wiring** (composer only — never the `.env`)

```bash
git add ../aeos365/composer.json ../aeos365/composer.lock ../aeos365-standalone/composer.json ../aeos365-standalone/composer.lock
git commit -m "chore(hosts): require aero/assistant (Aeon) in SaaS + standalone"
```

(The host repos may be separate git roots — commit within each host repo as the project normally does. Do NOT commit `.env`.)

---

## Self-Review

**1. Spec coverage (M1 slice of the design):**
- Provider-agnostic layer → Task 1 (contract) + Task 2 (binding) + Task 4 (Gemini driver). ✅
- Chat turn + persistence → Task 3 (models) + Task 5 (service). ✅
- `aeon_conversations` / `aeon_messages` with `blocks_json` → Task 3. ✅
- Drawer + `/aeon` page + `useAeon` + BlockRenderer (text-only) → Task 7. ✅
- Dual-mode (tenant + standalone) → Task 8 wires both. ✅
- Gemini default `gemini-flash-latest`, `x-goog-api-key`, endpoint → Task 2 config + Task 4. ✅
- RAG, tools, plan-gating, rich blocks → **deferred to M2–M4** (correctly out of M1 scope). ✅

**2. Placeholder scan:** none — every step has concrete code or an exact command. The only intentional placeholder is `GEMINI_API_KEY=PASTE_THE_REAL_KEY_HERE` in a host `.env` (a secret, never committed).

**3. Type consistency:**
- `AiProvider::chat/embed/isAvailable` signatures identical across contract (T1), stub (T2), Gemini (T4), Fake (T2). ✅
- `AiChatResult` props (`content, toolCalls, tokensUsed, model, success, error`) consistent T1↔T4↔T5. ✅
- `AeonService::send(int, ?int, string): array{conversation, reply}` consistent T5↔T6. ✅
- Endpoint JSON shape `{conversation_id, reply:{role,content,blocks}}` consistent T6↔T7 (`aeonClient` → `useAeon`). ✅
- Message block shape `{type:'text',text}` consistent T5 (persist) ↔ T6 (response) ↔ T7 (render). ✅

**Known adaptation points flagged inline for the implementer:** exact `@aero/ui` `Drawer`/`Card`/`Icon` prop names, the `DashboardLayout` `.layout` pattern, `TenantModel` connection behavior in testbench, the tenant-migrate command name, and the testbench major version. Each has a "confirm/mirror X" note at the relevant step.
