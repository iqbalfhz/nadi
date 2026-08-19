<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property int|null $department_id
 * @property string $password
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'is_active', 'department_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Every Shield permission tied to an /app module — used only to decide
     * whether a role should be let into the /app panel at all. Keep this in
     * sync whenever a new /app resource, page, or widget is added.
     *
     * @var list<string>
     */
    private const APP_PANEL_PERMISSIONS = [
        'ViewAny:RoomBooking',
        'Create:RoomBooking',
        'ViewAny:ObChecklist',
        'Create:ObChecklist',
        'ViewAny:MessengerDelivery',
        'Create:MessengerDelivery',
        'ViewAny:Ticket',
        'View:QueueOperator',
        'View:SecurityScan',
        'View:MessengerTasks',
        'View:SellTicket',
        'View:BookingCalendarWidget',
    ];

    /**
     * The migration's column default only takes effect once a row is
     * actually written — a freshly-constructed instance (factories, `new
     * User()`) stays null until refreshed from the database otherwise,
     * which broke canAccessPanel()'s strict bool return type.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Determine whether the user can access the given Filament panel.
     *
     * The /admin panel is for admin/HR staff (see docs/NADI.MD) — entry is
     * only gated by is_active; which resources/pages/widgets are actually
     * visible inside is controlled entirely by each one's Shield-generated
     * policy. /app is self-service, used daily by every regular employee,
     * so a misconfigured role should get a clear "no access" instead of a
     * confusing empty dashboard — entry there additionally requires holding
     * at least one permission relevant to an /app module.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->is_active,
            'app' => $this->is_active && $this->hasAnyPermission(self::APP_PANEL_PERMISSIONS),
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
