# Standalone Module Repository Setup Guide

This guide explains how to move a module to a separate repository with its own dependencies while still being able to use it in the main SaaS application.

## Overview

This approach allows you to:
- Develop and maintain a module in its own repository
- Run the module as a standalone application
- Include the module in the main SaaS platform via Composer

## Prerequisites

- Module created using `php artisan make:module {name}`
- Git repository hosting (GitHub, GitLab, etc.)
- Composer understanding

## Step 1: Prepare Module for Standalone Repository

### 1.1 Update Module's composer.json

Edit `modules/{YourModule}/composer.json` to make it a complete package:

```json
{
    "name": "your-org/your-module",
    "description": "Standalone module for Aero Enterprise Suite",
    "type": "library",
    "license": "proprietary",
    "authors": [
        {
            "name": "Your Name",
            "email": "your.email@example.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "illuminate/support": "^11.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "orchestra/testbench": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "Modules\\YourModule\\": ""
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Modules\\YourModule\\Tests\\": "Tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Modules\\YourModule\\Providers\\YourModuleServiceProvider"
            ]
        }
    }
}
```

### 1.2 Add Standalone Bootstrap (Optional)

Create `modules/{YourModule}/bootstrap/standalone.php`:

```php
<?php

/*
|--------------------------------------------------------------------------
| Standalone Module Bootstrap
|--------------------------------------------------------------------------
|
| This file is loaded when the module runs as a standalone application.
| It sets up the module-specific configuration and environment.
*/

use Illuminate\Foundation\Application;

// Create Laravel application instance
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Bind important interfaces
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    Modules\YourModule\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Modules\YourModule\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Modules\YourModule\Exceptions\Handler::class
);

return $app;
```

### 1.3 Add Standalone Configuration

Create `modules/{YourModule}/.env.example`:

```env
APP_NAME="Your Module"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_module_db
DB_USERNAME=root
DB_PASSWORD=

# Module-specific settings
YOUR_MODULE_FEATURE_X=true
YOUR_MODULE_API_KEY=
```

### 1.4 Update Module Config for Standalone Mode

Edit `modules/{YourModule}/Config/config.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Standalone Mode
    |--------------------------------------------------------------------------
    |
    | When true, the module runs as a standalone application.
    | When false, the module integrates with the main SaaS platform.
    */
    'standalone' => env('YOUR_MODULE_STANDALONE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | In standalone mode, use separate database connection.
    */
    'database' => [
        'connection' => env('YOUR_MODULE_DB_CONNECTION', 'mysql'),
        'prefix' => env('YOUR_MODULE_DB_PREFIX', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | In standalone mode, module can have its own authentication.
    */
    'auth' => [
        'enabled' => env('YOUR_MODULE_AUTH_ENABLED', true),
        'guard' => env('YOUR_MODULE_AUTH_GUARD', 'web'),
    ],

    // Your module-specific configurations here
];
```

## Step 2: Create Separate Repository

### 2.1 Initialize Git Repository

```bash
cd modules/{YourModule}

# Initialize git
git init

# Create .gitignore
cat > .gitignore << 'EOF'
/vendor/
/node_modules/
.env
.env.backup
.phpunit.result.cache
composer.lock
package-lock.json
EOF

# Initial commit
git add .
git commit -m "Initial commit: Standalone module"
```

### 2.2 Push to Remote Repository

```bash
# Add remote (GitHub example)
git remote add origin https://github.com/your-org/your-module.git

# Push to remote
git branch -M main
git push -u origin main
```

### 2.3 Tag Initial Version

```bash
# Create version tag
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0
```

## Step 3: Set Up Standalone Development

### 3.1 Clone for Standalone Development

```bash
# Clone the repository
git clone https://github.com/your-org/your-module.git

cd your-module

# Install dependencies
composer install
npm install  # If you have frontend assets
```

### 3.2 Configure Standalone Environment

```bash
# Copy environment file
cp .env.example .env

# Set standalone mode
echo "YOUR_MODULE_STANDALONE=true" >> .env

# Configure database
# Edit .env with your database credentials

# Generate app key (if needed)
php artisan key:generate
```

### 3.3 Run Migrations

```bash
# Run module migrations
php artisan migrate --path=Database/Migrations
```

### 3.4 Start Development Server

```bash
# Option 1: Using artisan serve (requires standalone bootstrap)
php artisan serve

# Option 2: Using Laravel Valet/Herd
# Just navigate to your-module.test in browser

# Option 3: Using Docker
docker-compose up -d
```

## Step 4: Include Module in Main SaaS

### 4.1 Option A: Via Composer (Recommended for Production)

Add to main application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-org/your-module.git"
        }
    ],
    "require": {
        "your-org/your-module": "^1.0"
    }
}
```

Then install:

```bash
composer require your-org/your-module
```

### 4.2 Option B: Via Path Repository (For Local Development)

Add to main application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../your-module",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "your-org/your-module": "@dev"
    }
}
```

Then install:

```bash
composer require your-org/your-module:@dev
```

This creates a symlink, so changes in the module are immediately reflected.

### 4.3 Register Module in Main SaaS

The module will auto-register via Laravel package discovery. If needed, you can manually register in `config/app.php`:

```php
'providers' => [
    // ...
    Modules\YourModule\Providers\YourModuleServiceProvider::class,
],
```

### 4.4 Discover Module

```bash
php artisan module:discover
php artisan module:list
```

## Step 5: Development Workflow

### 5.1 Standalone Development

```bash
# Work in standalone repository
cd your-module

# Make changes
# ... edit files ...

# Test
composer test

# Commit and push
git add .
git commit -m "Add new feature"
git push origin main

# Tag new version
git tag -a v1.0.1 -m "Version 1.0.1"
git push origin v1.0.1
```

### 5.2 Update in Main SaaS

```bash
# Update in main application
cd main-saas-app

# Update to latest version
composer update your-org/your-module

# Or update to specific version
composer require your-org/your-module:^1.0.1
```

### 5.3 Local Development with Symlink

When using path repository with symlink:

```bash
# Changes in module are immediately available in main app
cd your-module
# ... make changes ...

cd main-saas-app
# Changes are already reflected (due to symlink)
php artisan cache:clear
```

## Step 6: Standalone Docker Setup (Optional)

Create `modules/{YourModule}/docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - YOUR_MODULE_STANDALONE=true
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: your_module_db
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

Create `modules/{YourModule}/Dockerfile`:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Start server
CMD php artisan serve --host=0.0.0.0 --port=8000
```

Run with:

```bash
docker-compose up -d
```

## Step 7: Module Configuration for Dual Mode

Update module's service provider to handle both modes:

```php
<?php

namespace Modules\YourModule\Providers;

use App\Support\Module\BaseModuleServiceProvider;

class YourModuleServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleCode = 'your-module';
    protected string $modulePath = __DIR__ . '/..';
    protected string $moduleNamespace = 'Modules\\YourModule';

    public function register(): void
    {
        // Check if running in standalone mode
        if ($this->isStandalone()) {
            $this->registerStandaloneServices();
        } else {
            $this->registerSaaSServices();
        }

        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->isStandalone()) {
            $this->bootStandalone();
        } else {
            $this->bootSaaS();
        }
    }

    protected function isStandalone(): bool
    {
        return config('your-module.standalone', false);
    }

    protected function registerStandaloneServices(): void
    {
        // Register standalone-specific services
        // Example: Custom auth, different database connection, etc.
    }

    protected function registerSaaSServices(): void
    {
        // Register SaaS-specific services
        // Example: Tenant-aware services, plan restrictions, etc.
    }

    protected function bootStandalone(): void
    {
        // Bootstrap standalone-specific features
    }

    protected function bootSaaS(): void
    {
        // Bootstrap SaaS-specific features
    }
}
```

## Complete Workflow Example

### Scenario: HRM Module

```bash
# 1. Create module in main SaaS
cd /path/to/main-saas
php artisan make:module HRM --standalone --type=business

# 2. Prepare for standalone
cd modules/HRM
# Update composer.json with package details
# Add .env.example
# Update module.json

# 3. Create separate repository
git init
git add .
git commit -m "Initial HRM module"
git remote add origin https://github.com/your-org/hrm-module.git
git push -u origin main
git tag v1.0.0
git push origin v1.0.0

# 4. Remove module from main SaaS and add as dependency
cd /path/to/main-saas
rm -rf modules/HRM

# Add to composer.json
nano composer.json
# Add repository and require section

composer require your-org/hrm-module

# 5. Verify in main SaaS
php artisan module:discover
php artisan module:list

# 6. Develop standalone
cd /path/to/hrm-module
cp .env.example .env
# Configure standalone settings
composer install
php artisan migrate --path=Database/Migrations
php artisan serve

# Visit: http://localhost:8000
```

## Troubleshooting

### Module Not Loading in SaaS

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Dump autoload
composer dump-autoload

# Rediscover modules
php artisan module:discover
```

### Standalone Mode Not Working

Check:
1. `.env` has `YOUR_MODULE_STANDALONE=true`
2. `config/your-module.php` reads the env variable correctly
3. Service provider checks standalone mode properly

### Database Connection Issues

In standalone mode, ensure:
1. Database credentials in `.env` are correct
2. Database exists
3. Migrations have run: `php artisan migrate --path=Database/Migrations`

### Composer Dependency Conflicts

```bash
# Update main application dependencies
composer update

# Or require with specific version
composer require your-org/your-module:1.0.0
```

## Best Practices

1. **Versioning**: Use semantic versioning (1.0.0, 1.1.0, 2.0.0)
2. **Changelog**: Maintain CHANGELOG.md in module repository
3. **Testing**: Run tests before tagging releases
4. **Documentation**: Keep README.md updated in module repository
5. **CI/CD**: Set up GitHub Actions for automated testing
6. **Security**: Never commit `.env` files
7. **Dependencies**: Keep dependencies minimal and well-defined
8. **Backwards Compatibility**: Avoid breaking changes in minor versions

## Summary

This approach gives you:
- ✅ Module in separate repository with own dependencies
- ✅ Standalone development and deployment capability
- ✅ Integration with main SaaS via Composer
- ✅ Independent versioning and releases
- ✅ Shared codebase between standalone and SaaS modes
- ✅ Clean separation of concerns

You can now develop, test, and deploy modules independently while still using them in the main SaaS platform.
