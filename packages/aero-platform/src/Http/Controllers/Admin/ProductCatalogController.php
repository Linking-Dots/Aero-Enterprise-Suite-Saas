<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Product;
use Aero\Platform\Services\ProductCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $this->svc->save($this->validated($request, null));

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->svc->save($this->validated($request, $product->id), $product->id);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->svc->delete($product->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Product deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?string $id): array
    {
        return $request->validate([
            'name'                   => ['required', 'string', 'max:120'],
            'code'                   => [$id ? 'nullable' : 'required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', Rule::unique('products', 'code')->ignore($id)],
            'description'            => ['nullable', 'string', 'max:500'],
            'monthly_price'          => ['required', 'numeric', 'min:0', 'max:99999'],
            'yearly_price'           => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'is_active'              => ['boolean'],
            'is_marketplace_visible' => ['boolean'],
            'modules'                => ['required', 'array', 'min:1'],
            'modules.*'              => ['string', Rule::exists('modules', 'code')],
        ]);
    }
}
