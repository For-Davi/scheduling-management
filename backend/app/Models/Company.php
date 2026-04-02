<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Company extends Model
{
    use Billable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'subscription_expiring_soon',
        'buffer_time_minutes',
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'subscription_expiring_soon' => 'boolean',
            'buffer_time_minutes' => 'integer',
        ];
    }

    // Limites de recursos por plano
    public const PLAN_LIMITS = [
        'free' => ['employees' => 1, 'services' => 3],
        'basic' => ['employees' => 3, 'services' => 10],
        'advanced' => ['employees' => 10, 'services' => 30],
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    public function blockedDays(): HasMany
    {
        return $this->hasMany(BlockedDay::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isActive(): bool
    {
        if ($this->plan === 'free') {
            return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
        }

        return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
    }

    public function planLimit(string $resource): int
    {
        return self::PLAN_LIMITS[$this->plan][$resource] ?? 0;
    }

    /** @return Collection<int, Service> */
    public function activeServices(): Collection
    {
        return $this->services()->where('is_active', true)->get();
    }

    /** @return Collection<int, Employee> */
    public function activeEmployees(): Collection
    {
        return $this->employees()->where('is_active', true)->get();
    }
}
