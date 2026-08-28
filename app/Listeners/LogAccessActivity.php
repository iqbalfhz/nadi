<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

/**
 * Everything about *access* that isn't a row changing: who signed in, who
 * failed to, whose password or 2FA changed, and who was granted or lost a
 * role or permission.
 *
 * Model edits are covered by App\Concerns\LogsNadiActivity. These have no
 * model event to hang off, so they are listened for explicitly — and they
 * are the entries an audit actually starts from.
 */
class LogAccessActivity
{
    private const LOG_ACCESS = 'akses';

    private const LOG_PERMISSION = 'izin';

    /**
     * Registered explicitly rather than by discovery, and the methods are
     * named record* rather than handle* on purpose: Laravel auto-registers
     * every `handle*` method it finds in app/Listeners, so a handleLogin()
     * here would be bound twice — once by discovery, once by this method —
     * and every login would be written to the log twice over.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'recordLogin']);
        $events->listen(Logout::class, [self::class, 'recordLogout']);
        $events->listen(Failed::class, [self::class, 'recordFailed']);
        $events->listen(Lockout::class, [self::class, 'recordLockout']);
        $events->listen(PasswordReset::class, [self::class, 'recordPasswordReset']);
        $events->listen(PasswordUpdatedViaController::class, [self::class, 'recordPasswordUpdated']);
        $events->listen(TwoFactorAuthenticationEnabled::class, [self::class, 'recordTwoFactorEnabled']);
        $events->listen(TwoFactorAuthenticationConfirmed::class, [self::class, 'recordTwoFactorConfirmed']);
        $events->listen(TwoFactorAuthenticationDisabled::class, [self::class, 'recordTwoFactorDisabled']);
        $events->listen(RecoveryCodesGenerated::class, [self::class, 'recordRecoveryCodesGenerated']);
        $events->listen(RoleAttachedEvent::class, [self::class, 'recordRoleAttached']);
        $events->listen(RoleDetachedEvent::class, [self::class, 'recordRoleDetached']);
        $events->listen(PermissionAttachedEvent::class, [self::class, 'recordPermissionAttached']);
        $events->listen(PermissionDetachedEvent::class, [self::class, 'recordPermissionDetached']);
    }

    public function recordLogin(Login $event): void
    {
        $this->access($event->user, 'Login berhasil');
    }

    public function recordLogout(Logout $event): void
    {
        if ($event->user !== null) {
            $this->access($event->user, 'Logout');
        }
    }

    /**
     * The attempted username is recorded, the password never is — a failed
     * login is very often a typo in the email, and the whole point of this
     * entry is being able to see *which* account is being hammered.
     */
    public function recordFailed(Failed $event): void
    {
        activity(self::LOG_ACCESS)
            ->withProperty('email', $event->credentials['email'] ?? null)
            ->log('Login gagal');
    }

    public function recordLockout(Lockout $event): void
    {
        activity(self::LOG_ACCESS)
            ->withProperty('email', $event->request->input('email'))
            ->log('Login diblokir sementara (terlalu banyak percobaan)');
    }

    public function recordPasswordReset(PasswordReset $event): void
    {
        $this->access($event->user, 'Password direset lewat email');
    }

    public function recordPasswordUpdated(PasswordUpdatedViaController $event): void
    {
        $this->access($event->user, 'Password diubah');
    }

    public function recordTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->access($event->user, '2FA diaktifkan');
    }

    public function recordTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->access($event->user, '2FA dikonfirmasi');
    }

    public function recordTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->access($event->user, '2FA dimatikan');
    }

    public function recordRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->access($event->user, 'Kode cadangan 2FA dibuat ulang');
    }

    public function recordRoleAttached(RoleAttachedEvent $event): void
    {
        $this->permissionChange($event->model, 'Role diberikan', $event->rolesOrIds, 'role');
    }

    public function recordRoleDetached(RoleDetachedEvent $event): void
    {
        $this->permissionChange($event->model, 'Role dicabut', $event->rolesOrIds, 'role');
    }

    public function recordPermissionAttached(PermissionAttachedEvent $event): void
    {
        $this->permissionChange($event->model, 'Izin diberikan', $event->permissionsOrIds, 'permission');
    }

    public function recordPermissionDetached(PermissionDetachedEvent $event): void
    {
        $this->permissionChange($event->model, 'Izin dicabut', $event->permissionsOrIds, 'permission');
    }

    private function access(mixed $user, string $description): void
    {
        $logger = activity(self::LOG_ACCESS);

        if ($user instanceof User) {
            $logger->causedBy($user)->performedOn($user);
        }

        $logger->log($description);
    }

    /**
     * The *subject* is whoever gained or lost access (a user, or a role
     * whose permissions changed); the *causer* is whoever made the change,
     * resolved from the current session. Those are usually different people,
     * and an audit needs both.
     */
    private function permissionChange(mixed $model, string $description, mixed $namesOrIds, string $kind): void
    {
        $logger = activity(self::LOG_PERMISSION);

        if ($model instanceof Model) {
            $logger->performedOn($model);
        }

        $logger
            ->withProperty('items', $this->describe($namesOrIds, $kind))
            ->log($description);
    }

    /**
     * Spatie hands these over inconsistently: attaching passes primary keys
     * (collectRoles/collectPermissions resolve them first), while detaching
     * passes back whatever the caller supplied — a name, a model, or a
     * collection of either. An audit entry reading "1, 7, 12" is useless, so
     * everything is flattened to the names an admin sees in the Roles UI.
     *
     * @return array<int, string>
     */
    private function describe(mixed $namesOrIds, string $kind): array
    {
        $items = is_iterable($namesOrIds) ? $namesOrIds : [$namesOrIds];

        $described = [];
        $idsToResolve = [];

        foreach ($items as $item) {
            if (is_object($item) && isset($item->name)) {
                $described[] = (string) $item->name;

                continue;
            }

            if (is_int($item) || (is_string($item) && ctype_digit($item))) {
                $idsToResolve[] = (int) $item;

                continue;
            }

            $described[] = is_string($item) ? $item : (string) json_encode($item);
        }

        if ($idsToResolve !== []) {
            /** @var class-string<Model> $modelClass */
            $modelClass = config("permission.models.{$kind}");

            $described = [
                ...$described,
                ...$modelClass::query()->whereKey($idsToResolve)->pluck('name')->all(),
            ];
        }

        return $described;
    }
}
