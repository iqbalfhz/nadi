<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\ShortLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['target_url', 'created_by'])]
class ShortLink extends Model
{
    /** @use HasFactory<ShortLinkFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Short Link';
    }

    protected static function booted(): void
    {
        static::creating(function (self $shortLink): void {
            // `code` isn't fillable, so a new instance never has one set —
            // no need to guard against overwriting an existing value here.
            do {
                $code = Str::random(7);
            } while (static::query()->where('code', $code)->exists());

            $shortLink->code = $code;
        });
    }

    /**
     * Whether this link may redirect straight through without showing the
     * destination first.
     *
     * A short link lends this office's domain to somewhere else entirely, so
     * an unknown destination is confirmed with whoever clicked rather than
     * followed silently — see config/short-links.php.
     */
    public function hasTrustedDestination(): bool
    {
        $host = parse_url($this->target_url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        $trusted = config('short-links.trusted_hosts', []);
        $trusted = is_array($trusted) ? $trusted : [];

        // The app's own domain is always trusted — a short link pointing back
        // into NADI can't lead anywhere the person isn't already.
        $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($ownHost) && $ownHost !== '') {
            $trusted[] = $ownHost;
        }

        foreach ($trusted as $trustedHost) {
            $trustedHost = strtolower((string) $trustedHost);

            // Suffix match on a dot boundary only, so "google.com" matches
            // "drive.google.com" but never "google.com.attacker.test".
            if ($host === $trustedHost || str_ends_with($host, '.'.$trustedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anything that isn't plain http/https — javascript:, data:, file: — has
     * no business being a redirect target, whatever slipped past the form.
     */
    public function hasFollowableScheme(): bool
    {
        $scheme = parse_url($this->target_url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'last_clicked_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function shortUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => URL::to("/s/{$this->code}"),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
