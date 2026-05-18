<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Inertia\Inertia;
use Inertia\Response;

class CareerPathController extends SelfServiceController
{
    public function index(): Response
    {
        return Inertia::render('HRM/SelfService/CareerPath', [
            'path' => null,
            'milestones' => collect(),
            'progression' => null,
        ]);
    }
}
