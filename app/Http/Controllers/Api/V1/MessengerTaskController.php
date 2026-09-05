<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MessengerDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MessengerDeliveryResource;
use App\Models\ApiUpload;
use App\Models\MessengerDelivery;
use App\Support\MediaAccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tugas Messenger over the API.
 *
 * The only field module with a state machine, and the only one where a step
 * must not be queued offline — see claim().
 *
 * Every transition is delegated to the model methods, which already hold the
 * row locks and the status guards and are already covered by the web's tests.
 * Nothing here re-implements them.
 */
class MessengerTaskController extends Controller
{
    private const PERMISSION = 'View:MessengerTasks';

    /**
     * Deliveries nobody has picked up yet — module-wide, not scoped to the
     * caller. That is the point of self-pickup: a courier needs to see what
     * is available before it is theirs.
     */
    public function open(Request $request): AnonymousResourceCollection
    {
        $this->authorizeCourier($request);

        $deliveries = MessengerDelivery::query()
            ->where('status', MessengerDeliveryStatus::Available)
            ->with('sender.department')
            ->oldest('id')
            ->paginate(20);

        return MessengerDeliveryResource::collection($deliveries);
    }

    /**
     * What this courier is currently carrying.
     */
    public function mine(Request $request): AnonymousResourceCollection
    {
        $this->authorizeCourier($request);

        $deliveries = MessengerDelivery::query()
            ->where('messenger_id', $request->user()->id)
            ->whereIn('status', [
                MessengerDeliveryStatus::PickedUp,
                MessengerDeliveryStatus::InTransit,
            ])
            ->with('sender.department')
            ->latest('claimed_at')
            ->paginate(20);

        return MessengerDeliveryResource::collection($deliveries);
    }

    /**
     * Take an open task.
     *
     * **This is the one step the app must never queue offline.** Two couriers
     * can tap the same task at the same moment; the model settles it with a
     * row lock, and the loser is told so. An offline claim would look like
     * success on the handset and silently lose — the courier would walk to
     * collect a document somebody else already took.
     */
    public function claim(Request $request, int $delivery): JsonResponse
    {
        $this->authorizeCourier($request);

        try {
            $claimed = MessengerDelivery::claim($delivery, $request->user());
        } catch (RuntimeException $exception) {
            return $this->conflict($exception);
        }

        return (new MessengerDeliveryResource($claimed->load('sender.department')))->response();
    }

    public function transit(Request $request, MessengerDelivery $messengerDelivery): JsonResponse
    {
        $this->authorizeCourier($request);

        try {
            $messengerDelivery->markInTransit($request->user());
        } catch (RuntimeException $exception) {
            return $this->conflict($exception);
        }

        return (new MessengerDeliveryResource($messengerDelivery->refresh()->load('sender.department')))->response();
    }

    /**
     * Mark delivered, with the proof photo that makes it a record rather than
     * a claim.
     */
    public function deliver(Request $request, MessengerDelivery $messengerDelivery): JsonResponse
    {
        $this->authorizeCourier($request);

        $data = $request->validate([
            // Singular, because the proof collection is ->singleFile().
            'photo_id' => [
                'required',
                'uuid',
                Rule::exists('api_uploads', 'id')->where('user_id', $request->user()->id),
            ],
        ], [
            'photo_id.required' => __('Foto bukti pengiriman wajib dilampirkan.'),
            'photo_id.exists' => __('Foto tidak ditemukan. Unggah ulang foto tersebut.'),
        ]);

        try {
            $messengerDelivery->markDelivered($request->user());
        } catch (RuntimeException $exception) {
            return $this->conflict($exception);
        }

        ApiUpload::claim($messengerDelivery, [$data['photo_id']], $request->user()->id, 'proof');

        return (new MessengerDeliveryResource($messengerDelivery->refresh()->load('sender.department')))->response();
    }

    public function proof(Request $request, MessengerDelivery $messengerDelivery): JsonResponse
    {
        $this->authorizeCourier($request);

        abort_unless(
            $messengerDelivery->messenger_id === $request->user()->id,
            Response::HTTP_FORBIDDEN,
        );

        MediaAccessLog::record($messengerDelivery, __('Foto Bukti Pengiriman'));

        $expiresAt = now()->addMinutes(30);

        $photos = $messengerDelivery->getMedia('proof')->map(fn ($media): array => [
            'id' => $media->uuid,
            'url' => $media->getTemporaryUrl($expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return response()->json(['data' => $photos]);
    }

    /**
     * The model's refusals already read as Indonesian sentences written for
     * the person holding the phone ("Tugas ini sudah diambil messenger
     * lain."), so they pass through as-is.
     *
     * Caught here rather than mapped globally: only these call sites are
     * known to throw a RuntimeException whose message is safe to show, and a
     * blanket rule would sooner or later put a stack-trace-shaped sentence on
     * a courier's screen.
     */
    private function conflict(RuntimeException $exception): JsonResponse
    {
        return response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_CONFLICT,
        );
    }

    private function authorizeCourier(Request $request): void
    {
        abort_unless($request->user()?->can(self::PERMISSION), Response::HTTP_FORBIDDEN);
    }
}
