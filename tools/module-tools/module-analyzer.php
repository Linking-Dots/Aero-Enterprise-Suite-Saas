#!/usr/bin/env php
<?php
/**
 * Module Dependency Analyzer
 * 
 * Analyzes a module's dependencies to help with manual extraction.
 * Does NOT auto-extract - provides report for developer review.
 * 
 * Usage:
 *   php tools/module-tools/module-analyzer.php <module-code>
 * 
 * Example:
 *   php tools/module-tools/module-analyzer.php hrm
 */

require __DIR__ . '/../../vendor/autoload.php';

class ModuleAnalyzer
{
    protected string $basePath;
    protected string $moduleCode;
    protected array $report = [];
    
    public function __construct(string $basePath, string $moduleCode)
    {
        $this->basePath = $basePath;
        $this->moduleCode = $moduleCode;
    }
    
    public function analyze(): array
    {
        echo "Analyzing module: {$this->moduleCode}\n\n";
        
        $this->report['module'] = $this->moduleCode;
        $this->report['analyzed_at'] = date('Y-m-d H:i:s');
        
        // Analyze different aspects
        $this->analyzeModels();
        $this->analyzeControllers();
        $this->analyzeMigrations();
        $this->analyzeRoutes();
        $this->analyzeFrontend();
        $this->analyzeDependencies();
        $this->analyzeSharedCode();
        
        return $this->report;
    }
    
    protected function analyzeModels(): void
    {
        echo "📦 Analyzing Models...\n";
        
        $models = $this->findFiles('app/Models', '*' . ucfirst($this->moduleCode) . '*.php');
        $models = array_merge($models, $this->findFiles('app/Models', '*Employee*.php'));
        $models = array_merge($models, $this->findFiles('app/Models', '*Department*.php'));
        
        $this->report['models'] = [
            'count' => count($models),
            'files' => $models,
            'relationships' => $this->analyzeModelRelationships($models),
        ];
        
        echo "   Found " . count($models) . " models\n";
    }
    
    protected function analyzeControllers(): void
    {
        echo "🎮 Analyzing Controllers...\n";
        
        $pattern = '*' . ucfirst($this->moduleCode) . '*Controller.php';
        $controllers = $this->findFiles('app/Http/Controllers', $pattern);
        
        $this->report['controllers'] = [
            'count' => count($controllers),
            'files' => $controllers,
            'middleware' => $this->analyzeControllerMiddleware($controllers),
        ];
        
        echo "   Found " . count($controllers) . " controllers\n";
    }
    
    protected function analyzeMigrations(): void
    {
        echo "🗄️  Analyzing Migrations...\n";
        
        $migrations = $this->findFiles('database/migrations', '*' . $this->moduleCode . '*.php');
        $migrations = array_merge($migrations, $this->findFiles('database/migrations', '*employee*.php'));
        $migrations = array_merge($migrations, $this->findFiles('database/migrations', '*department*.php'));
        
        $this->report['migrations'] = [
            'count' => count($migrations),
            'files' => $migrations,
            'foreign_keys' => $this->analyzeForeignKeys($migrations),
        ];
        
        echo "   Found " . count($migrations) . " migrations\n";
    }
    
    protected function analyzeRoutes(): void
    {
        echo "🛣️  Analyzing Routes...\n";
        
        $routeFiles = ['routes/web.php', 'routes/tenant.php', 'routes/api.php'];
        $routes = [];
        
        foreach ($routeFiles as $file) {
            $fullPath = $this->basePath . '/' . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $moduleRoutes = $this->extractModuleRoutes($content);
                if (!empty($moduleRoutes)) {
                    $routes[$file] = $moduleRoutes;
                }
            }
        }
        
        $this->report['routes'] = $routes;
        
        echo "   Found routes in " . count($routes) . " files\n";
    }
    
    protected function analyzeFrontend(): void
    {
        echo "🎨 Analyzing Frontend...\n";
        
        $pages = $this->findFiles('resources/js/Tenant/Pages', '*' . ucfirst($this->moduleCode) . '*');
        $pages = array_merge($pages, $this->findFiles('resources/js/Tenant/Pages', '*Employee*'));
        
        $components = $this->findFiles('resources/js/Components', '*' . ucfirst($this->moduleCode) . '*');
        $forms = $this->findFiles('resources/js/Forms', '*' . ucfirst($this->moduleCode) . '*');
        $tables = $this->findFiles('resources/js/Tables', '*' . ucfirst($this->moduleCode) . '*');
        
        $this->report['frontend'] = [
            'pages' => ['count' => count($pages), 'files' => $pages],
            'components' => ['count' => count($components), 'files' => $components],
            'forms' => ['count' => count($forms), 'files' => $forms],
            'tables' => ['count' => count($tables), 'files' => $tables],
        ];
        
        echo "   Found " . count($pages) . " pages, " . count($components) . " components\n";
    }
    
    protected function analyzeDependencies(): void
    {
        echo "🔗 Analyzing Dependencies...\n";
        
        $dependencies = [];
        
        // Check for external service dependencies
        $services = $this->findFiles('app/Services/Shared', '*.php');
        foreach ($services as $service) {
            $content = file_get_contents($this->basePath . '/' . $service);
            if (stripos($content, $this->moduleCode) !== false) {
                $dependencies['services'][] = $service;
            }
        }
        
        // Check for core dependencies
        $coreServices = [
            'ModuleAccessService',
            'ProfileUpdateService',
            'MailService',
            'TenantProvisioner',
        ];
        
        foreach ($coreServices as $coreService) {
            $found = $this->grepInFiles($coreService);
            if (!empty($found)) {
                $dependencies['core'][$coreService] = $found;
            }
        }
        
        $this->report['dependencies'] = $dependencies;
        
        echo "   Found " . count($dependencies) . " dependency types\n";
    }
    
    protected function analyzeSharedCode(): void
    {
        echo "📚 Analyzing Shared Code...\n";
        
        $sharedCode = [];
        
        // Check what shared code this module uses
        $sharedDirs = [
            'app/Services/Shared',
            'app/Services/Platform',
            'resources/js/Components',
            'resources/js/Hooks',
        ];
        
        foreach ($sharedDirs as $dir) {
            $sharedCode[$dir] = $this->analyzeSharedUsage($dir);
        }
        
        $this->report['shared_code'] = $sharedCode;
        
        echo "   Analyzed shared code usage\n";
    }
    
    // Helper methods
    
    protected function findFiles(string $directory, string $pattern): array
    {
        $fullPath = $this->basePath . '/' . $directory;
        
        if (!is_dir($fullPath)) {
            return [];
        }
        
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                if (fnmatch($pattern, $filename)) {
                    $relativePath = str_replace($this->basePath . '/', '', $file->getPathname());
                    $files[] = $relativePath;
                }
            }
        }
        
        return $files;
    }
    
    protected function analyzeModelRelationships(array $models): array
    {
        $relationships = [];
        
        foreach ($models as $modelFile) {
            $content = file_get_contents($this->basePath . '/' . $modelFile);
            
            // Find relationship methods
            preg_match_all('/public function (\w+)\(\).*?return \$this->(hasOne|hasMany|belongsTo|belongsToMany)\((.*?)\);/s', $content, $matches);
            
            if (!empty($matches[0])) {
                $modelName = basename($modelFile, '.php');
                $relationships[$modelName] = [];
                
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $relationships[$modelName][] = [
                        'method' => $matches[1][$i],
                        'type' => $matches[2][$i],
                        'related' => trim($matches[3][$i]),
                    ];
                }
            }
        }
        
        return $relationships;
    }
    
    protected function analyzeControllerMiddleware(array $controllers): array
    {
        $middleware = [];
        
        foreach ($controllers as $controllerFile) {
            $content = file_get_contents($this->basePath . '/' . $controllerFile);
            
            // Find middleware definitions
            if (preg_match_all('/->middleware\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
                $controllerName = basename($controllerFile, '.php');
                $middleware[$controllerName] = array_unique($matches[1]);
            }
        }
        
        return $middleware;
    }
    
    protected function analyzeForeignKeys(array $migrations): array
    {
        $foreignKeys = [];
        
        foreach ($migrations as $migrationFile) {
            $content = file_get_contents($this->basePath . '/' . $migrationFile);
            
            // Find foreign key constraints
            if (preg_match_all('/->foreign\([\'"]([^\'"]+)[\'"]\).*?->references\([\'"]([^\'"]+)[\'"]\).*?->on\([\'"]([^\'"]+)[\'"]\)/s', $content, $matches)) {
                $migrationName = basename($migrationFile, '.php');
                $foreignKeys[$migrationName] = [];
                
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $foreignKeys[$migrationName][] = [
                        'column' => $matches[1][$i],
                        'references' => $matches[2][$i],
                        'on' => $matches[3][$i],
                    ];
                }
            }
        }
        
        return $foreignKeys;
    }
    
    protected function extractModuleRoutes(string $content): array
    {
        $routes = [];
        
        // Simple pattern matching for routes
        $patterns = [
            '/Route::(get|post|put|patch|delete)\([\'"]([^\'"]*)' . $this->moduleCode . '([^\'"]*)[\'"]/i',
            '/Route::resource\([\'"]([^\'"]*)' . $this->moduleCode . '([^\'"]*)[\'"]/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $routes = array_merge($routes, $matches[0]);
            }
        }
        
        return array_unique($routes);
    }
    
    protected function analyzeSharedUsage(string $directory): array
    {
        $usage = ['used' => 0, 'files' => []];
        
        $fullPath = $this->basePath . '/' . $directory;
        
        if (!is_dir($fullPath)) {
            return $usage;
        }
        
        $files = $this->findFiles($directory, '*.php');
        $files = array_merge($files, $this->findFiles($directory, '*.jsx'));
        
        foreach ($files as $file) {
            $content = file_get_contents($this->basePath . '/' . $file);
            if (stripos($content, $this->moduleCode) !== false) {
                $usage['used']++;
                $usage['files'][] = $file;
            }
        }
        
        return $usage;
    }
    
    protected function grepInFiles(string $needle): array
    {
        $found = [];
        $dirs = ['app', 'resources/js'];
        
        foreach ($dirs as $dir) {
            $fullPath = $this->basePath . '/' . $dir;
            
            if (!is_dir($fullPath)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && (str_ends_with($file->getFilename(), '.php') || str_ends_with($file->getFilename(), '.jsx'))) {
                    $content = file_get_contents($file->getPathname());
                    if (stripos($content, $needle) !== false) {
                        $relativePath = str_replace($this->basePath . '/', '', $file->getPathname());
                        $found[] = $relativePath;
                    }
                }
            }
        }
        
        return array_unique($found);
    }
    
    public function printReport(): void
    {
        echo "\n";
        echo "=" . str_repeat("=", 60) . "\n";
        echo "  MODULE ANALYSIS REPORT: " . strtoupper($this->moduleCode) . "\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Models
        echo "📦 MODELS (" . $this->report['models']['count'] . ")\n";
        foreach ($this->report['models']['files'] as $file) {
            echo "   - $file\n";
        }
        if (!empty($this->report['models']['relationships'])) {
            echo "\n   Relationships:\n";
            foreach ($this->report['models']['relationships'] as $model => $rels) {
                echo "   • $model:\n";
                foreach ($rels as $rel) {
                    echo "      - {$rel['method']}() -> {$rel['type']}({$rel['related']})\n";
                }
            }
        }
        echo "\n";
        
        // Controllers
        echo "🎮 CONTROLLERS (" . $this->report['controllers']['count'] . ")\n";
        foreach ($this->report['controllers']['files'] as $file) {
            echo "   - $file\n";
        }
        echo "\n";
        
        // Migrations
        echo "🗄️  MIGRATIONS (" . $this->report['migrations']['count'] . ")\n";
        foreach ($this->report['migrations']['files'] as $file) {
            echo "   - $file\n";
        }
        if (!empty($this->report['migrations']['foreign_keys'])) {
            echo "\n   ⚠️  Foreign Keys Found:\n";
            foreach ($this->report['migrations']['foreign_keys'] as $migration => $keys) {
                echo "   • $migration:\n";
                foreach ($keys as $key) {
                    echo "      - {$key['column']} -> {$key['on']}.{$key['references']}\n";
                }
            }
        }
        echo "\n";
        
        // Frontend
        echo "🎨 FRONTEND\n";
        echo "   Pages: " . $this->report['frontend']['pages']['count'] . "\n";
        echo "   Components: " . $this->report['frontend']['components']['count'] . "\n";
        echo "   Forms: " . $this->report['frontend']['forms']['count'] . "\n";
        echo "   Tables: " . $this->report['frontend']['tables']['count'] . "\n";
        echo "\n";
        
        // Dependencies
        if (!empty($this->report['dependencies'])) {
            echo "🔗 DEPENDENCIES\n";
            if (!empty($this->report['dependencies']['core'])) {
                echo "   Core Services:\n";
                foreach ($this->report['dependencies']['core'] as $service => $files) {
                    echo "   • $service (used in " . count($files) . " files)\n";
                }
            }
            echo "\n";
        }
        
        // Recommendations
        echo "💡 RECOMMENDATIONS\n";
        echo "   1. Review foreign key relationships before extraction\n";
        echo "   2. Extract core service dependencies to aero-core package\n";
        echo "   3. Ensure all shared UI components are in aero-core\n";
        echo "   4. Create module-specific service provider\n";
        echo "   5. Write comprehensive tests before extraction\n";
        echo "\n";
        
        // Export report
        $reportFile = $this->basePath . "/storage/module-analysis-{$this->moduleCode}.json";
        file_put_contents($reportFile, json_encode($this->report, JSON_PRETTY_PRINT));
        echo "📄 Full report saved to: $reportFile\n\n";
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage: php module-analyzer.php <module-code>\n";
    echo "Example: php module-analyzer.php hrm\n";
    exit(1);
}

$moduleCode = $argv[1];
$basePath = dirname(dirname(__DIR__));

$analyzer = new ModuleAnalyzer($basePath, $moduleCode);
$analyzer->analyze();
$analyzer->printReport();

echo "✅ Analysis complete!\n\n";
