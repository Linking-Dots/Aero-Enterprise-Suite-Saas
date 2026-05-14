<?php
declare(strict_types=1);

$broken = 0;
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('packages'));
foreach ($iter as $f) {
    if ($f->getExtension() !== 'php') continue;
    $p = str_replace('\\', '/', $f->getPathname());
    if (!str_contains($p, '/src/')) continue;
    foreach (['aero-core','aero-hrmac','aero-auth','aero-contracts','aero-platform'] as $s) {
        if (str_contains($p, '/' . $s . '/')) continue 2;
    }
    $c = file_get_contents($p);
    if (str_contains($c, 'extends TenantModel') &&
        !str_contains($c, 'use Aero\Contracts\Models\TenantModel') &&
        !str_contains($c, 'use Aero\Core\Models\TenantModel')) {
        echo "STILL BROKEN: $p\n";
        $broken++;
    }
}
echo "Broken remaining: $broken\n";
