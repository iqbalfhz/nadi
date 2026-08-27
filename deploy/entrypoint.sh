#!/bin/sh
set -e

# Only the "app" (web) service sets RUN_SETUP=true in docker-compose.yml —
# the reverb/scheduler services share this same image but skip this block,
# so migrations and cache-rebuilding don't run redundantly (or race) across
# three containers starting at once.
if [ "$RUN_SETUP" = "true" ]; then
    php artisan package:discover --ansi
    php artisan filament:upgrade
    php artisan migrate --force
    # Both seeders are idempotent (firstOrCreate/updateOrCreate), safe to
    # run on every deploy. ShieldSeeder must run first — AdminUserSeeder
    # assigns the super_admin role, which only exists after it.
    php artisan db:seed --class=ShieldSeeder --force
    php artisan db:seed --class=AdminUserSeeder --force
    php artisan storage:link || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    # Without this, Filament re-discovers every resource, page and widget by
    # walking app/Filament (160+ files, across both panels) on every single
    # request. Safe to cache here because each deploy is a fresh container,
    # so this always rebuilds — never run it on a dev machine, where a stale
    # cache hides newly added or removed components.
    php artisan filament:optimize
fi

exec "$@"
