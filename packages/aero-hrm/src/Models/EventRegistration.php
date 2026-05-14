<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\HRM\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventRegistration extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'token',
        'full_name',
        'email',
        'phone',
        'address',
        'organization',
        'department',
        'gender',
        'payment_proof',
        'payment_verified',
        'payment_verified_at',
        'payment_verified_by',
        'admin_notes',
        'qr_code',
        'status',
        'rejection_reason',
        'custom_fields',
    ];

    protected $casts = [
        'payment_verified' => 'boolean',
        'payment_verified_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    protected $appends = ['status_badge_color'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($registration) {
            if (empty($registration->token)) {
                $registration->token = static::generateUniqueToken();
            }
        });

        static::created(function ($registration) {
            $registration->generateQrCode();
        });
    }

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function subEvents(): BelongsToMany
    {
        return $this->belongsToMany(SubEvent::class, 'event_registration_sub_events')
            ->withTimestamps();
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    // Accessors
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'default',
            default => 'default',
        };
    }

    // Helper methods
    public static function generateUniqueToken(): string
    {
        do {
            $token = 'EVT-'.strtoupper(substr(uniqid(), -8));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function generateQrCode(): void
    {
        $url = route('events.registration.verify', $this->token);

        $qrCodePath = 'qrcodes/events/'.$this->event_id;
        $qrCodeFilename = $this->token.'.svg';

        if (! file_exists(storage_path('app/public/'.$qrCodePath))) {
            mkdir(storage_path('app/public/'.$qrCodePath), 0755, true);
        }

        QrCode::format('svg')
            ->size(300)
            ->generate($url, storage_path('app/public/'.$qrCodePath.'/'.$qrCodeFilename));

        $this->update(['qr_code' => $qrCodePath.'/'.$qrCodeFilename]);
    }

    public function approve(?User $approver = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'payment_verified' => true,
            'payment_verified_at' => now(),
            'payment_verified_by' => $approver?->id,
        ]);
    }

    public function reject(string $reason, ?User $rejector = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'payment_verified_by' => $rejector?->id,
        ]);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    public function getTotalFee(): float
    {
        return $this->subEvents->sum('joining_fee');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePaymentVerified($query)
    {
        return $query->where('payment_verified', true);
    }
}
