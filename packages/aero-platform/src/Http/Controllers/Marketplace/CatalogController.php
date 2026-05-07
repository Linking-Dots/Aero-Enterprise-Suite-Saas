<?php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\Product;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(): Response
    {
        $products = Product::marketplaceVisible()
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Marketplace/Catalog', [
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'icon' => $p->icon,
                'monthly_price' => $p->monthly_price,
                'yearly_price' => $p->yearly_price,
                'currency' => $p->currency,
                'metadata' => $p->metadata,
            ]),
        ]);
    }

    public function show(string $code): Response
    {
        $product = Product::marketplaceVisible()
            ->where('code', $code)
            ->firstOrFail();

        return Inertia::render('Marketplace/ProductDetail', ['product' => $product]);
    }
}
