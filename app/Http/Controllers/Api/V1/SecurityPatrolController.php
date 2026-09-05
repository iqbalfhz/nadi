<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSecurityPatrolRequest;
use App\Http\Resources\Api\V1\SecurityCheckpointResource;
use App\Http\Resources\Api\V1\SecurityPatrolResource;
use App\Models\ApiUpload;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use App\Support\FieldReportTime;
use App\Support\MediaAccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Patroli Security over the API.
 *
 * Differs from Checklist OB in one important way: there is no endpoint that
 * lists checkpoints. See resolve() for why.
 *
 * Authorisation everywhere is 'View:SecurityScan', the page permission the web
 * uses — SecurityPatrolPolicy has no owner clause, so its view() would refuse
 * the very guard who filed the report.
 */
class SecurityPatrolController extends Controller
{
    private const PERMISSION = 'View:SecurityScan';

    /**
     * Turn one scanned QR code into a checkpoint name.
     *
     * Deliberately one at a time, with no "list all checkpoints" companion.
     * The code on the sticker *is* the evidence that a guard walked to the
     * post; handing the app every code would let a whole round be filed from
     * the canteen. You can only ask about a code you already hold, which you
     * only hold by having been there.
     *
     * The cost is that a first-ever scan of a post needs signal to show its
     * name. The app should cache the code-to-name pairs it has resolved, and
     * for an unrecognised code offline simply say the post will be confirmed
     * when the report is sent — the report itself still queues fine.
     */
    public function resolve(Request $request, string $code): SecurityCheckpointResource|JsonResponse
    {
        $this->authorizeGuard($request);

        return $this->answerFor($code);
    }

    /**
     * The same lookup, but taking whatever the scanner actually read.
     *
     * Stickers hold a URL, not a bare code (see
     * SecurityCheckpoint::codeFromScan). A URL cannot go in a path segment
     * — its slashes would route the request somewhere else entirely, which
     * is exactly how the first field test failed: the app froze with no
     * message because the request never reached this controller.
     *
     * So the raw scan travels as a query parameter, and the extraction
     * happens on the side that owns the sticker format.
     */
    public function scan(Request $request): SecurityCheckpointResource|JsonResponse
    {
        $this->authorizeGuard($request);

        $scanned = trim((string) $request->query('scanned', ''));

        if ($scanned === '') {
            return response()->json([
                'message' => __('Hasil pindai kosong. Coba pindai ulang QR-nya.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->answerFor(SecurityCheckpoint::codeFromScan($scanned));
    }

    private function answerFor(string $code): SecurityCheckpointResource|JsonResponse
    {
        $checkpoint = SecurityCheckpoint::query()->where('code', $code)->first();

        // Two causes, two different things for the guard to do, so they get
        // two different sentences. Told only "not found", someone standing
        // at a post that was retired this morning rescans until they give up.
        //
        // Answered here rather than through abort(): the exception mapping in
        // bootstrap/app.php deliberately ignores exception messages, because
        // framework-thrown ones carry English ("Too Many Attempts.").
        if (! $checkpoint instanceof SecurityCheckpoint) {
            return response()->json([
                'message' => __('QR ini tidak dikenali. Pastikan stikernya milik NADI, lalu pindai ulang.'),
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $checkpoint->is_active) {
            return response()->json([
                'message' => __('Titik patroli ini sudah dinonaktifkan. Laporkan ke pengawas — memindai ulang tidak akan berhasil.'),
            ], Response::HTTP_GONE);
        }

        return new SecurityCheckpointResource($checkpoint);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeGuard($request);

        $patrols = SecurityPatrol::query()
            ->where('user_id', $request->user()->id)
            ->with('checkpoint')
            ->latest('id')
            ->paginate(20);

        return SecurityPatrolResource::collection($patrols);
    }

    public function store(StoreSecurityPatrolRequest $request): JsonResponse
    {
        $data = $request->validated();

        $checkpoint = SecurityCheckpoint::query()
            ->where('code', $data['checkpoint_code'])
            ->where('is_active', true)
            ->firstOrFail();

        $patrol = SecurityPatrol::create([
            'security_checkpoint_id' => $checkpoint->id,
            'user_id' => $request->user()->id,
            'incident_report' => $data['incident_report'] ?? null,
            'submitted_at' => FieldReportTime::clamp($data['submitted_at'] ?? null),
        ]);

        ApiUpload::claim($patrol, $data['photo_ids'], $request->user()->id);

        return (new SecurityPatrolResource($patrol->load('checkpoint')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function photos(Request $request, SecurityPatrol $securityPatrol): JsonResponse
    {
        $this->authorizeGuard($request);
        $this->authorizeOwnership($request, $securityPatrol);

        MediaAccessLog::record($securityPatrol, __('Foto Patroli Security'));

        $expiresAt = now()->addMinutes(30);

        $photos = $securityPatrol->getMedia('photos')->map(fn ($media): array => [
            'id' => $media->uuid,
            'url' => $media->getTemporaryUrl($expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return response()->json(['data' => $photos]);
    }

    private function authorizeGuard(Request $request): void
    {
        abort_unless($request->user()?->can(self::PERMISSION), Response::HTTP_FORBIDDEN);
    }

    /**
     * Reads are scoped to the guard's own rounds. Everyone's patrols are
     * visible in /admin, which is where oversight belongs — the phone shows
     * you what you did, not what your colleagues did.
     */
    private function authorizeOwnership(Request $request, SecurityPatrol $patrol): void
    {
        abort_unless($patrol->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);
    }
}
