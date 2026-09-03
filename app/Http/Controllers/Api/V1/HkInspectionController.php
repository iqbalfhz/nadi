<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\HkCondition;
use App\Enums\HkShift;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreHkInspectionRequest;
use App\Http\Resources\Api\V1\HkCategoryResource;
use App\Http\Resources\Api\V1\HkInspectionResource;
use App\Jobs\SendHkInspectionToTelegram;
use App\Models\ApiUpload;
use App\Models\HkArea;
use App\Models\HkCategory;
use App\Models\HkInspection;
use App\Support\FieldReportTime;
use App\Support\MediaAccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inspeksi HK over the API.
 *
 * The most intricate of the four field modules: a two-level picker, two
 * conditional fields, a category the client never sends, and a Telegram
 * delivery that must not be able to fail the report.
 */
class HkInspectionController extends Controller
{
    /**
     * Categories with their points, in one call.
     *
     * Nested rather than two endpoints because the app needs the whole tree
     * cached before it goes offline anyway — and `requires_floor` travels with
     * the category, which is what lets the phone render the conditional Lantai
     * field without asking the server.
     */
    public function categories(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HkInspection::class);

        $categories = HkCategory::query()
            ->where('is_active', true)
            ->with(['areas' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return HkCategoryResource::collection($categories);
    }

    /**
     * The picklists the form needs beyond the category tree. Served from the
     * server so a label change never requires a new app release.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', HkInspection::class);

        return response()->json([
            'data' => [
                'shifts' => $this->enumOptions(HkShift::cases()),
                'conditions' => collect(HkCondition::cases())
                    ->map(fn (HkCondition $case): array => [
                        'value' => $case->value,
                        'label' => $case->label(),
                        // Lets the app require Tindak Lanjut without hardcoding
                        // which conditions count as a finding.
                        'needs_follow_up' => $case->needsFollowUp(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HkInspection::class);

        $inspections = HkInspection::query()
            ->where('user_id', $request->user()->id)
            ->with(['category', 'area'])
            ->latest('id')
            ->paginate(20);

        return HkInspectionResource::collection($inspections);
    }

    public function show(HkInspection $hkInspection): HkInspectionResource
    {
        $this->authorize('view', $hkInspection);

        return new HkInspectionResource($hkInspection->load(['category', 'area']));
    }

    public function store(StoreHkInspectionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Read the category back off the chosen point rather than trusting a
        // value from the client. Every report in /admin is filtered by
        // category, so a mismatched pair would file the report somewhere
        // nobody looks. Same reasoning as CreateHkInspection.
        $area = HkArea::query()->with('category')->findOrFail((int) $data['hk_area_id']);

        $condition = HkCondition::from($data['condition']);

        $inspection = HkInspection::create([
            'hk_category_id' => $area->hk_category_id,
            'hk_area_id' => $area->id,
            'user_id' => $request->user()->id,
            'staff_name' => $data['staff_name'],
            'shift' => $data['shift'],
            'condition' => $condition,
            // Belt-and-braces alongside the request rules: a floor is only
            // meaningful where the category asks for one, and a follow-up only
            // where there was something to follow up.
            'floor' => $area->category->requires_floor ? ($data['floor'] ?? null) : null,
            'follow_up' => $condition->needsFollowUp() ? ($data['follow_up'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
            'submitted_at' => FieldReportTime::clamp($data['submitted_at'] ?? null),
        ]);

        ApiUpload::claim($inspection, $data['photo_ids'], $request->user()->id);

        // Dispatched only once the photos are attached, so the job finds them —
        // and after the report is safely committed, because Telegram being
        // down is not the supervisor's problem to wait on.
        SendHkInspectionToTelegram::dispatch($inspection->getKey());

        return (new HkInspectionResource($inspection->load(['category', 'area'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function photos(HkInspection $hkInspection): JsonResponse
    {
        $this->authorize('view', $hkInspection);

        MediaAccessLog::record($hkInspection, __('Foto Inspeksi HK'));

        $expiresAt = now()->addMinutes(30);

        $photos = $hkInspection->getMedia('photos')->map(fn ($media): array => [
            'id' => $media->uuid,
            'url' => $media->getTemporaryUrl($expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return response()->json(['data' => $photos]);
    }

    /**
     * @param  array<int, HkShift>  $cases
     * @return array<int, array<string, string>>
     */
    private function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(fn (HkShift $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])
            ->all();
    }
}
