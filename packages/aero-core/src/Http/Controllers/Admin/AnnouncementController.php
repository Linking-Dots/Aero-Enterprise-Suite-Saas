<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreAnnouncementRequest;
use Aero\Core\Http\Requests\UpdateAnnouncementRequest;
use Aero\Core\Models\Announcement;
use Aero\Core\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Core/Announcements/Index', [
            'announcements' => $this->announcementService->list($request->only('search', 'status')),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->announcementService->create($request->validated(), $request->user());

        return back()->with('success', 'Announcement created.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->announcementService->update($announcement, $request->validated(), $request->user());

        return back()->with('success', 'Announcement updated.');
    }

    public function publish(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->publish($announcement, $request->user());

        return back()->with('success', 'Announcement published.');
    }

    public function archive(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->archive($announcement, $request->user());

        return back()->with('success', 'Announcement archived.');
    }

    public function destroy(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->delete($announcement, $request->user());

        return back()->with('success', 'Announcement deleted.');
    }

    public function banners(Request $request): Response
    {
        $banners = $this->announcementService->list(
            array_merge($request->only('search', 'status'), ['audience' => 'banner'])
        );

        return Inertia::render('Core/Announcements/Banners', [
            'banners' => $banners,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function storeBanner(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['audience' => 'banner', 'type' => 'warning']);
        $this->announcementService->create($data, $request->user());

        return back()->with('success', 'Banner created.');
    }
}
