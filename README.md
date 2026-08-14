# NADI

**NADI** is an internal office operations platform — a single web application that brings several day-to-day operational workflows (room booking, visitor queueing, document numbering, facility checklists, security patrols, internal document delivery, and event ticketing) together under one login, instead of separate spreadsheets and manual processes.

The name comes from *nadi* (Indonesian for "pulse"/"artery") — the idea of one shared system that keeps the different parts of daily operations connected.

## Modules

- **Room Booking** — interactive calendar booking for meeting rooms, with self-service booking and admin oversight.
- **Queue Management** — kiosk ticket-taking, a real-time public display screen, and an operator console for calling numbers, with daily reporting.
- **Document Numbering** — automatic, sequential official document numbers across multiple document types, companies, and departments.
- **Facility Checklists** — photo-based checklist submissions for cleaning/facility rounds and security patrols (the latter triggered by scanning a per-location QR code).
- **Internal Document Delivery** — a self-pickup task board for messengers to claim and track internal document deliveries end to end.
- **Event Ticketing** — a lightweight point-of-sale flow for one-off ticketed events, with cashier sales entry, receipt printing, and sales reporting.

## Tech stack

- [Laravel](https://laravel.com) 13 with the official Livewire starter kit
- [Livewire](https://livewire.laravel.com) 4
- [Filament](https://filamentphp.com) 5 (multi-panel: admin and employee self-service)
- [Filament Shield](https://github.com/bezhanSalleh/filament-shield) for role/permission management
- [Laravel Reverb](https://laravel.com/docs/reverb) for real-time updates (queue display)
- [Laravel Fortify](https://laravel.com/docs/fortify) for authentication (including 2FA and passkeys)
- MySQL

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate

npm run build
php artisan serve
```

Configure your database and mail settings in `.env` before running migrations. See `docs/NADI.MD` for a deeper look at the project's architecture and design decisions.

## Testing

```bash
composer test
```

This runs code style checks (Pint), static analysis (PHPStan/Larastan), and the full automated test suite.

## License

This is a proprietary internal application. All rights reserved — no license is granted for reuse, distribution, or modification.
