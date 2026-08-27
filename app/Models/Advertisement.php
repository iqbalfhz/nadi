<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\AdvertisementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'is_active', 'sort_order', 'duration_seconds'])]
class Advertisement extends Model implements HasMedia
{
    /** @use HasFactory<AdvertisementFactory> */
    use HasFactory, InteractsWithMedia, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Iklan Layar';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'video/mp4',
                'video/webm',
            ]);
    }

    public function isVideo(): bool
    {
        $media = $this->getFirstMedia('file');

        if (! $media) {
            return false;
        }

        return str_starts_with($media->mime_type, 'video/');
    }
}
