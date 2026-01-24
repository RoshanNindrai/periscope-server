<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Periscope\SearchModule\Contracts\SearchableUser;

class User extends Authenticatable implements SearchableUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'phone',     // PII: encrypted at rest, do not expose in API
        'phone_hash', // used only for lookup
    ];

    /**
     * Encrypt phone at rest and set phone_hash for lookup.
     */
    protected function setPhoneAttribute(string $value): void
    {
        $this->attributes['phone'] = Crypt::encryptString($value);
        $this->attributes['phone_hash'] = hash('sha256', $value);
    }

    /**
     * Decrypt phone when needed (e.g. for SMS).
     */
    protected function getPhoneAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user has verified their phone number.
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Mark the user's phone as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Route notifications for the SMS channel.
     */
    public function routeNotificationForSms($notification): ?string
    {
        return $this->phone;
    }

    // SearchableUser interface implementation
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPhoneVerifiedAt(): ?Carbon
    {
        return $this->phone_verified_at;
    }

    /**
     * CamelCase accessor for GraphQL (phoneVerifiedAt). Reads from attributes
     * to avoid recursion, since Laravel also uses this for 'phone_verified_at'.
     */
    public function getPhoneVerifiedAtAttribute(): ?Carbon
    {
        $raw = $this->attributes['phone_verified_at'] ?? null;
        return $raw === null ? null : $this->asDateTime($raw);
    }
}
