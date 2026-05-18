<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Inertia\Inertia;
use Inertia\Response;

class BenefitController extends SelfServiceController
{
    public function index(): Response
    {
        return Inertia::render('HRM/SelfService/Benefits', [
            'benefits' => collect(),
        ]);
    }
}
