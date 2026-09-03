<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Takes one photo and hands back an id the report can claim later.
 *
 * Uploading is a step of its own because a field worker's connection drops
 * mid-transfer as a matter of routine. Bundled into the report submission,
 * every retry would re-send every photo from the start; split, each photo
 * that lands stays landed and the report itself is a few hundred bytes of
 * JSON.
 */
class UploadController extends Controller
{
    /**
     * 10 MB, matching the SpatieMediaLibraryFileUpload maxSize on every
     * evidence form in /app. PHP's own limits sit above this
     * (deploy/php.ini), so this is the rule that actually bites.
     */
    private const MAX_KILOBYTES = 10240;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                // By content, not by file extension — and the same three types
                // the media collections accept, so nothing gets this far only
                // to be refused on the way into the collection.
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'photo.required' => __('Tidak ada foto yang dikirim.'),
            'photo.image' => __('Berkas yang dikirim bukan gambar.'),
            'photo.mimetypes' => __('Format foto harus JPG, PNG, atau WEBP.'),
            'photo.max' => __('Ukuran foto maksimal 10 MB.'),
        ]);

        $file = $request->file('photo');

        $upload = ApiUpload::create([
            'user_id' => $request->user()->id,
            'path' => $file->store('staging', ApiUpload::DISK),
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        return response()->json([
            'data' => [
                'id' => $upload->id,
                'expires_at' => $upload->created_at
                    ->addDays(ApiUpload::RETENTION_DAYS)
                    ->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }
}
