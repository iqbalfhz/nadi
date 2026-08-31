<?php

namespace App\Jobs;

use App\Models\HkInspection;
use App\Settings\TelegramSettings;
use App\Support\HkInspectionMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Posts a filed inspection into the housekeeping Telegram group.
 *
 * Queued rather than sent inline: a supervisor standing in a toilet on mall
 * wifi must never wait on — or be blocked by — Telegram's API. The report is
 * already saved by the time this runs, so every failure path here costs a
 * notification, never the record.
 */
class SendHkInspectionToTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Telegram rate-limits per chat, and mall wifi drops. Backing off in
     * minutes rather than seconds gives both time to recover.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $inspectionId) {}

    public function handle(TelegramSettings $settings): void
    {
        if (! $settings->isReady()) {
            return;
        }

        // Deleted between filing and delivery — nothing to report, and no
        // reason to burn retries on it.
        $inspection = HkInspection::query()->with(['category', 'area', 'user'])->find($this->inspectionId);

        if ($inspection === null) {
            return;
        }

        $caption = HkInspectionMessage::for($inspection);

        /** @var array<int, Media> $photos */
        $photos = $inspection->getMedia('photos')->all();

        match (true) {
            $photos === [] => $this->sendMessage($settings, $caption),
            count($photos) === 1 => $this->sendPhoto($settings, $photos[0], $caption),
            default => $this->sendMediaGroup($settings, $photos, $caption),
        };
    }

    private function sendMessage(TelegramSettings $settings, string $caption): void
    {
        $this->assertOk($this->request()->asJson()->post($settings->endpoint('sendMessage'), [
            'chat_id' => $settings->chat_id,
            'text' => $caption,
        ]), 'sendMessage');
    }

    private function sendPhoto(TelegramSettings $settings, Media $photo, string $caption): void
    {
        // Evidence photos live on the private 'internal' disk, so Telegram
        // cannot fetch them by URL — the bytes go up as a multipart upload
        // instead. See HkInspection::registerMediaCollections().
        $this->assertOk(
            $this->request()
                ->attach('photo', $this->contentsOf($photo), $photo->file_name)
                ->post($settings->endpoint('sendPhoto'), [
                    'chat_id' => $settings->chat_id,
                    'caption' => $caption,
                ]),
            'sendPhoto',
        );
    }

    /**
     * @param  array<int, Media>  $photos
     */
    private function sendMediaGroup(TelegramSettings $settings, array $photos, string $caption): void
    {
        // Telegram caps an album at 10; extra photos stay viewable in /admin.
        $photos = array_slice($photos, 0, 10);

        $request = $this->request();
        $media = [];

        foreach ($photos as $index => $photo) {
            $name = "photo{$index}";

            $request = $request->attach($name, $this->contentsOf($photo), $photo->file_name);

            $media[] = array_filter([
                'type' => 'photo',
                'media' => "attach://{$name}",
                // Only the first item carries the caption — Telegram shows it
                // once for the whole album.
                'caption' => $index === 0 ? $caption : null,
            ]);
        }

        $this->assertOk($request->post($settings->endpoint('sendMediaGroup'), [
            'chat_id' => $settings->chat_id,
            'media' => json_encode($media),
        ]), 'sendMediaGroup');
    }

    private function request(): PendingRequest
    {
        return Http::timeout(30)->connectTimeout(10);
    }

    private function contentsOf(Media $photo): string
    {
        $stream = $photo->stream();
        $contents = stream_get_contents($stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($contents === false) {
            throw new RuntimeException("Foto laporan HK tidak terbaca: media #{$photo->getKey()}.");
        }

        return $contents;
    }

    private function assertOk(Response $response, string $method): void
    {
        if ($response->successful()) {
            return;
        }

        // Telegram answers with its own JSON error ("chat not found", "Unauthorized")
        // which is exactly the machine text that must never reach a screen —
        // it belongs here, in the log, where an admin can be pointed at it.
        Log::error('Gagal mengirim laporan HK ke Telegram.', [
            'method' => $method,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
            'inspection_id' => $this->inspectionId,
        ]);

        throw new RuntimeException("Telegram menolak {$method} (HTTP {$response->status()}).");
    }
}
