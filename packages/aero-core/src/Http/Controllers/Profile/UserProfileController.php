<?php

namespace Aero\Core\Http\Controllers\Profile;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Core/Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->profile_image ?? $user->avatar_url ?? null,
                'roles' => $user->getRoleNames()->toArray(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['profile_image' => Storage::url($path)]);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $this->audit->log(
            AuditEventType::RECORD_UPDATED->value,
            'profile_updated',
            $user,
            'User profile updated'
        );

        return back()->with('success', 'Profile updated.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($request->password)]);

        $this->audit->log(
            AuditEventType::PASSWORD_RESET->value,
            'password_changed',
            $user,
            'Password changed by user'
        );

        return back()->with('success', 'Password updated.');
    }
}
