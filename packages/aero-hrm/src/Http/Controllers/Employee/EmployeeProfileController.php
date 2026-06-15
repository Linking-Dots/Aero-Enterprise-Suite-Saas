<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Core\Models\User;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\HR\UpdateEmployeeProfileRequest;
use Aero\HRM\Models\EmergencyContact;
use Aero\HRM\Models\EmployeeBankDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employee Profile Controller
 *
 * Handles bank details and emergency contacts management for employee profiles.
 * Supports updating bank details and multiple emergency contacts in a single form submission.
 *
 * @see \Aero\HRM\Models\EmployeeBankDetail
 * @see \Aero\HRM\Models\EmergencyContact
 */
class EmployeeProfileController extends Controller
{
    /**
     * Display the employee profile with all related data.
     */
    public function show(User $user): Response
    {
        // Eager load all profile-related data
        $user->load([
            'bankDetail',
            'emergencyContacts' => fn ($query) => $query->ordered(),
            'personalDocuments' => fn ($query) => $query->latest()->take(5),
            'addresses',
            'department',
            'designation',
        ]);

        return Inertia::render('HRM/Profile/EmployeeProfile', [
            'title' => 'Employee Profile',
            'employee' => $this->formatEmployeeData($user),
            'canEdit' => $this->canEditProfile($user),
        ]);
    }

    /**
     * Get employee profile data for editing.
     */
    public function edit(User $user): JsonResponse
    {
        $user->load(['bankDetail', 'emergencyContacts' => fn ($query) => $query->ordered()]);

        return response()->json([
            'success' => true,
            'employee' => $this->formatEmployeeData($user),
            'bank_details' => $user->bankDetail,
            'emergency_contacts' => $user->emergencyContacts,
        ]);
    }

    /**
     * Update employee profile - handles both bank details and emergency contacts.
     *
     * This method accepts nested arrays for bank_details and emergency_contacts,
     * allowing a single form submission to update both sections.
     *
     * Request structure:
     * {
     *   "bank_details": { "bank_name": "...", "account_number": "...", ... },
     *   "emergency_contacts": [
     *     { "id": null, "name": "...", "phone": "...", ... },
     *     { "id": 1, "name": "...", "phone": "...", ... },
     *     { "id": 2, "_delete": true }
     *   ]
     * }
     */
    public function update(UpdateEmployeeProfileRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $messages = [];

            // Handle Bank Details update (1:1 relationship)
            if (isset($validated['bank_details']) && ! empty($validated['bank_details'])) {
                $this->updateBankDetails($user, $validated['bank_details']);
                $messages[] = 'Bank details updated successfully.';
            }

            // Handle Emergency Contacts update (1:Many relationship)
            if (isset($validated['emergency_contacts'])) {
                $this->updateEmergencyContacts($user, $validated['emergency_contacts']);
                $messages[] = 'Emergency contacts updated successfully.';
            }

            DB::commit();

            // Refresh the user with updated relationships
            $user->load(['bankDetail', 'emergencyContacts' => fn ($query) => $query->ordered()]);

            Log::info('Employee profile updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'sections' => array_keys(array_filter([
                    'bank_details' => isset($validated['bank_details']),
                    'emergency_contacts' => isset($validated['emergency_contacts']),
                ])),
            ]);

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'employee' => $this->formatEmployeeData($user),
                'bank_details' => $user->bankDetail,
                'emergency_contacts' => $user->emergencyContacts,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update employee profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update only bank details.
     */
    public function updateBankDetails(User $user, array $bankData): EmployeeBankDetail
    {
        // updateOrCreate handles both insert and update for 1:1 relationship
        return $user->bankDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bank_name' => $bankData['bank_name'],
                'branch_name' => $bankData['branch_name'] ?? null,
                'account_holder_name' => $bankData['account_holder_name'],
                'account_number' => $bankData['account_number'], // Auto-encrypted via cast
                'swift_code' => $bankData['swift_code'] ?? null,
                'iban' => $bankData['iban'] ?? null,
                'routing_number' => $bankData['routing_number'] ?? null,
                'account_type' => $bankData['account_type'] ?? 'savings',
                'tax_id' => $bankData['tax_id'] ?? null, // Auto-encrypted via cast
                'currency' => $bankData['currency'] ?? 'USD',
                'is_primary' => true,
            ]
        );
    }

    /**
     * Update emergency contacts with support for add, update, and delete operations.
     *
     * @param  array  $contactsData  Array of contact data with optional _delete flag
     */
    public function updateEmergencyContacts(User $user, array $contactsData): void
    {
        $processedIds = [];
        $priority = 1;

        foreach ($contactsData as $contactData) {
            // Skip empty entries
            if (empty($contactData['name']) && empty($contactData['phone'])) {
                continue;
            }

            // Handle delete
            if (! empty($contactData['_delete']) && ! empty($contactData['id'])) {
                EmergencyContact::where('id', $contactData['id'])
                    ->where('user_id', $user->id)
                    ->delete();

                continue;
            }

            // Prepare contact data
            $data = [
                'user_id' => $user->id,
                'name' => $contactData['name'],
                'relationship' => $contactData['relationship'],
                'phone' => $contactData['phone'],
                'alternate_phone' => $contactData['alternate_phone'] ?? null,
                'email' => $contactData['email'] ?? null,
                'address' => $contactData['address'] ?? null,
                'city' => $contactData['city'] ?? null,
                'country' => $contactData['country'] ?? null,
                'priority' => $contactData['priority'] ?? $priority,
                'is_primary' => $priority === 1, // First contact is primary
                'notify_on_emergency' => $contactData['notify_on_emergency'] ?? true,
            ];

            if (! empty($contactData['id'])) {
                // Update existing contact
                $contact = EmergencyContact::where('id', $contactData['id'])
                    ->where('user_id', $user->id)
                    ->first();

                if ($contact) {
                    $contact->update($data);
                    $processedIds[] = $contact->id;
                }
            } else {
                // Create new contact
                $contact = EmergencyContact::create($data);
                $processedIds[] = $contact->id;
            }

            $priority++;
        }

        // Note: We don't auto-delete contacts not in the submission
        // This prevents accidental data loss. Use explicit _delete flag instead.
    }

    /**
     * Get bank details for an employee.
     */
    public function getBankDetails(User $user): JsonResponse
    {
        $bankDetail = $user->bankDetail;

        return response()->json([
            'success' => true,
            'bank_details' => $bankDetail,
            'masked_account_number' => $bankDetail?->masked_account_number,
        ]);
    }

    /**
     * Get emergency contacts for an employee.
     */
    public function getEmergencyContacts(User $user): JsonResponse
    {
        $contacts = $user->emergencyContacts()->ordered()->get();

        return response()->json([
            'success' => true,
            'emergency_contacts' => $contacts,
            'primary_contact' => $contacts->firstWhere('is_primary', true),
        ]);
    }

    /**
     * Add a single emergency contact.
     */
    public function addEmergencyContact(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:3'],
            'is_primary' => ['nullable', 'boolean'],
            'notify_on_emergency' => ['nullable', 'boolean'],
        ]);

        // Determine priority
        $maxPriority = $user->emergencyContacts()->max('priority') ?? 0;
        $validated['priority'] = $maxPriority + 1;
        $validated['user_id'] = $user->id;

        // If this is set as primary, unset others
        if (! empty($validated['is_primary'])) {
            $user->emergencyContacts()->update(['is_primary' => false]);
        }

        $contact = EmergencyContact::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Emergency contact added successfully.',
            'contact' => $contact,
        ], 201);
    }

    /**
     * Delete an emergency contact.
     */
    public function deleteEmergencyContact(User $user, EmergencyContact $contact): JsonResponse
    {
        if ($contact->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        $wasPrimary = $contact->is_primary;
        $contact->delete();

        // If deleted contact was primary, make the next highest priority contact primary
        if ($wasPrimary) {
            $user->emergencyContacts()->ordered()->first()?->update(['is_primary' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency contact deleted successfully.',
        ]);
    }

    /**
     * Verify bank details (HR/Admin action).
     */
    public function verifyBankDetails(User $user): JsonResponse
    {
        $bankDetail = $user->bankDetail;

        if (! $bankDetail) {
            return response()->json([
                'success' => false,
                'message' => 'No bank details found.',
            ], 404);
        }

        $bankDetail->markAsVerified(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Bank details verified successfully.',
            'bank_details' => $bankDetail->fresh(),
        ]);
    }

    /**
     * Format employee data for response.
     */
    private function formatEmployeeData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'employee_id' => $user->employee?->id,
            'employee_code' => $user->employee?->employee_code,
            'profile_image_url' => $user->profile_image_url,
            'department' => $user->employee?->department?->only(['id', 'name']),
            'designation' => $user->employee?->designation?->only(['id', 'title']),
            'date_of_joining' => $user->employee?->date_of_joining,
            'active' => ! $user->trashed(),
        ];
    }

    /**
     * Check if current user can edit the profile.
     */
    private function canEditProfile(User $user): bool
    {
        return Auth::check() && (
            Auth::user()->can('hr.employees.update') ||
            Auth::user()->can('profile.own.update') ||
            Auth::id() === $user->id
        );
    }
}
