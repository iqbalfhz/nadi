<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCrashReportRequest;
use App\Models\AppCrashReport;
use App\Support\FieldReportTime;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only way a failure in the field reaches anyone.
 *
 * The APK is handed to officers directly, so there is no store-provided crash
 * reporting at all. The app already catches every unhandled failure —
 * FlutterError.onError, PlatformDispatcher.onError, ErrorWidget.builder,
 * runZonedGuarded — and until this endpoint existed all of it stopped at the
 * device log.
 *
 * Not behind EnsureIdempotency, and not by omission. That middleware exists so
 * a retried report never becomes two reports; here, a repeat *is* the
 * information — five hundred of the same crash is a different fact from one.
 * Grouping in the model does the deduplication instead, and does it across
 * devices, which an idempotency key could not.
 */
class CrashReportController extends Controller
{
    public function __invoke(StoreCrashReportRequest $request): JsonResponse
    {
        AppCrashReport::record(
            $request->crash(),
            // Clamped exactly like a field report's submitted_at: a crash is
            // queued on the handset and sent when there is signal, which for a
            // crash is often much later.
            FieldReportTime::clamp($request->string('occurred_at')->toString() ?: null) ?? now(),
            $request->user()?->id,
        );

        // Bare 201. The app does not read the body, and a crash reporter that
        // hands back detail is a crash reporter that can itself fail while
        // reporting a failure.
        return response()->json(status: Response::HTTP_CREATED);
    }
}
