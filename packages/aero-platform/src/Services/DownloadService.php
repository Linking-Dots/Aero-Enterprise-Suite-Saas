<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadService
{
    /**
     * Generate a time-limited signed download URL for a license (48h).
     */
    public function generateSignedUrl(StandaloneLicense $license): string
    {
        return URL::temporarySignedRoute(
            'marketplace.download',
            now()->addHours(48),
            [
                'license' => $license->id,
                'product' => $license->product->code,
            ]
        );
    }

    /**
     * Stream the product ZIP to the browser.
     */
    public function stream(StandaloneLicense $license): StreamedResponse
    {
        $productCode = $license->product->code;
        $version = $license->product->version;
        $path = "releases/{$productCode}-v{$version}-standalone.zip";
        $filename = "{$productCode}-v{$version}-standalone.zip";

        if (! Storage::exists($path)) {
            abort(404, "Release file not found for {$productCode} v{$version}");
        }

        return Storage::download($path, $filename);
    }
}
