<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreObChecklistRequest;
use App\Http\Resources\Api\V1\ObAreaResource;
use App\Http\Resources\Api\V1\ObChecklistResource;
use App\Models\ApiUpload;
use App\Models\ObArea;
use App\Models\ObChecklist;
use App\Support\FieldReportTime;
use App\Support\MediaAccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checklist OB over the API — the reference module for the mobile app.
 *
 * The shape here is what the other three field modules copy: a scoped list, a
 * create that trusts nothing from the payload it can derive itself, photos
 * claimed from the staging table, and a photo-reading endpoint that leaves an
 * audit entry behind.
 */
class ObChecklistController extends Controller
{
    /**
     * The areas the picker offers. Small and slow-changing, so the app caches
     * it — without this list a worker in a basement has nothing to pick from.
     */
    public function areas(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ObChecklist::class);

        $areas = ObArea::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ObAreaResource::collection($areas);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ObChecklist::class);

        $checklists = ObChecklist::query()
            // Scoped the same way ObChecklistResource::getEloquentQuery() is
            // in /app: a worker sees their own reports and no one else's.
            ->where('user_id', $request->user()->id)
            ->with('area')
            ->latest('id')
            ->paginate(20);

        return ObChecklistResource::collection($checklists);
    }

    public function show(ObChecklist $obChecklist): ObChecklistResource
    {
        // ObChecklistPolicy::view() already allows the owner through, so this
        // reuses the web's rule rather than restating it.
        $this->authorize('view', $obChecklist);

        return new ObChecklistResource($obChecklist->load('area'));
    }

    public function store(StoreObChecklistRequest $request): JsonResponse
    {
        $data = $request->validated();

        $checklist = ObChecklist::create([
            'ob_area_id' => $data['ob_area_id'],
            // Always the authenticated user, never the payload — the same
            // rule CreateObChecklist::mutateFormDataBeforeCreate() enforces.
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
            'submitted_at' => FieldReportTime::clamp($data['submitted_at'] ?? null),
        ]);

        ApiUpload::claim($checklist, $data['photo_ids'], $request->user()->id);

        return (new ObChecklistResource($checklist->load('area')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Signed, expiring links to a report's photos.
     *
     * The collection lives on the private 'internal' disk, so there is no
     * permanent URL to hand out and each view mints its own.
     */
    public function photos(ObChecklist $obChecklist): JsonResponse
    {
        $this->authorize('view', $obChecklist);

        MediaAccessLog::record($obChecklist, __('Foto Checklist OB'));

        $expiresAt = now()->addMinutes(30);

        $photos = $obChecklist->getMedia('photos')->map(fn ($media): array => [
            'id' => $media->uuid,
            'url' => $media->getTemporaryUrl($expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return response()->json(['data' => $photos]);
    }
}
