<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Recruitment;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Recruitment\StoreOfferRequest;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobOffer;
use Aero\HRM\Services\Recruitment\OfferService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferService $service,
    ) {}

    public function create(): Response
    {
        $this->authorize('hrm.recruitment.offer-letters.send');

        return Inertia::render('HRM/Recruitment/Offers/Create', [
            'applications' => JobApplication::select(['id', 'first_name', 'last_name', 'job_id'])
                ->where('status', 'shortlisted')
                ->with('job:id,title,salary_currency')
                ->get(),
            'templates' => $this->service->templates(),
        ]);
    }

    public function store(StoreOfferRequest $request): RedirectResponse
    {
        $offer = $this->service->extend($request->validated(), $request->user());

        return redirect()
            ->route('hrm.recruitment.offers.show', $offer)
            ->with('success', 'Offer sent successfully.');
    }

    public function show(JobOffer $offer): Response
    {
        $this->authorize('hrm.recruitment.offer-letters.view');

        $offer->load([
            'application:id,first_name,last_name,job_id,status',
            'application.job:id,title',
            'sentBy:id,name',
        ]);

        return Inertia::render('HRM/Recruitment/Offers/Show', [
            'offer' => $offer,
        ]);
    }
}
