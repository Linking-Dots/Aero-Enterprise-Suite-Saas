<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\ProductCatalogService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Products (Catalog) command centre — the monetisation-governance surface.
 *
 * Presents the sellable products (bundled modules, price, adoption, MRR), the
 * dev→customer lifecycle, and a demoted "system modules" tray. This is the
 * industry-standard product-catalog admin; the technical module registry lives
 * on the separate Modules page.
 */
class ProductCatalogController extends Controller
{
    public function __construct(private ProductCatalogService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Products/Index', $this->svc->overview());
    }
}
