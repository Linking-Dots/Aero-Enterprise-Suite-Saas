<?php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\StandaloneLicense;
use Aero\Platform\Services\DownloadService;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(private readonly DownloadService $downloads) {}

    public function download(string $licenseId): StreamedResponse
    {
        $license = StandaloneLicense::with('product')->findOrFail($licenseId);

        if (! $license->isActive()) {
            abort(403, 'License is not active.');
        }

        return $this->downloads->stream($license);
    }
}
