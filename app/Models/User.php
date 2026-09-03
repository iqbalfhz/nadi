<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
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
use Laravel\Sanctum\HasApiTokens;
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
    use HasApiTokens, HasFactory, HasRoles, LogsNadiActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public static function activitySubjectLabel(): string
    {
        return 'Pengguna';
    }

    /**
     * Every Shield permission tied to an /app module — used only to decide
     * whether a role should be let into the /app panel at all. Keep this in
     * sync whenever a new /app resource or page is added. Widgets never
     * belong here — they carry no permission of their own (see any widget
     * with $isDiscovered = false for why).
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
        'View:BookingCalendar',
        'ViewAny:ShortLink',
        'Create:ShortLink',
        'ViewAny:Barcode',
        'View:GenerateBarcode',
        'ViewAny:VendorSale',
        'View:SellVendorProduct',
        'ViewAny:HkInspection',
        'Create:HkInspection',
    ];

    /**
     * The permissions that open the mobile app, and the module key each one
     * unlocks — a subset of APP_PANEL_PERMISSIONS above, not a copy of it.
     * The phone carries the four field modules, the ones done while walking
     * the mall; the desk-bound ones (POS, queue, room booking) stay on the
     * web, where a printer and a stable connection are.
     *
     * Kept next to APP_PANEL_PERMISSIONS deliberately. Forgetting to update
     * that constant is the mistake this project makes most often (NADI.MD
     * §8.1); there are now two of them, so at least they are in one place.
     *
     * @var array<string, string>
     */
    private const MOBILE_MODULE_PERMISSIONS = [
        'Create:ObChecklist' => 'ob',
        'View:SecurityScan' => 'security',
        'Create:HkInspection' => 'hk',
        'View:MessengerTasks' => 'messenger',
    ];

    /**
     * Whether this account may sign in to the mobile app at all — the API's
     * equivalent of canAccessPanel(), which is Filament-only.
     */
    public function canUseMobileApp(): bool
    {
        return $this->is_active && $this->mobileModules() !== [];
    }

    /**
     * The module keys this user's phone should show, driving the mobile home
     * screen the same way QuickLinksWidget drives /app's.
     *
     * Read live from the permission tables rather than baked into the token:
     * an admin who revokes a role at noon expects that to take effect at
     * noon, not at the user's next sign-in.
     *
     * @return list<string>
     */
    public function mobileModules(): array
    {
        // Membership test rather than hasPermissionTo(), which throws when the
        // permission row itself is missing — that happens on any database
        // where shield:generate hasn't run, and a home screen is no place to
        // discover it.
        $held = $this->getAllPermissions()->pluck('name');

        $modules = [];

        foreach (self::MOBILE_MODULE_PERMISSIONS as $permission => $module) {
            if ($held->contains($permission)) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Whether this user holds any permission that isn't purely /app
     * self-service. Two jobs: it gates entry to /admin (see
     * canAccessPanel()), and it picks the panel to land on after login (the
     * `dashboard` route in routes/web.php).
     */
    public function hasAnyAdminPermission(): bool
    {
        return $this->getAllPermissions()
            ->pluck('name')
            ->diff(self::APP_PANEL_PERMISSIONS)
            ->isNotEmpty();
    }

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
     * Both panels require holding a permission that actually belongs to
     * them, so a misconfigured role gets a clear "no access" rather than a
     * confusing half-empty panel.
     *
     * For /admin that isn't only about tidiness. Several permissions are
     * shared between the two panels by necessity — an employee needs
     * ViewAny:RoomBooking to see their *own* bookings in /app — but the
     * matching /admin resource is deliberately unscoped, listing everyone's
     * records. Gating entry on is_active alone therefore handed every
     * employee the company-wide view of room bookings, OB checklists (and
     * their photos), courier deliveries, short links and barcodes, since
     * their own /app permission satisfied the resource policy on the way in.
     * hasAnyAdminPermission() is what separates the two populations: it
     * ignores anything in APP_PANEL_PERMISSIONS.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->is_active && $this->hasAnyAdminPermission(),
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
