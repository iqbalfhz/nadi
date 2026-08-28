<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Opens a record's evidence photos in a modal.
 *
 * These collections live on the private 'internal' disk, so there is no
 * plain URL to link to — every image has to be handed out as a short-lived
 * signed URL, generated per view. Before this existed the admin tables only
 * showed a photo *count*, with no way to actually look at one.
 */
class ViewMediaAction
{
    public static function make(string $collection, string $label = 'Lihat Foto'): Action
    {
        return Action::make('viewMedia')
            ->label($label)
            ->icon(Heroicon::OutlinedPhoto)
            ->color('gray')
            ->modalHeading($label)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->visible(fn (Model $record): bool => $record instanceof HasMedia && $record->getMedia($collection)->isNotEmpty())
            // mountUsing, not action(): the modal has no submit button, so an
            // action() callback would never fire — this has to be recorded
            // when the modal opens.
            //
            // Opening it hands out signed URLs to evidence photos, and
            // reading data leaves no trace of its own: nothing is written, so
            // no model event fires. Without this, the one action that
            // actually exposes the photos would be the one thing Riwayat
            // Aktivitas couldn't see.
            ->mountUsing(function (Model $record) use ($label): void {
                activity('akses-data')
                    ->performedOn($record)
                    ->withProperty('data', $label)
                    ->log('Lihat foto');
            })
            ->modalContent(fn (Model $record) => view('filament.partials.media-gallery', [
                'media' => $record instanceof HasMedia ? $record->getMedia($collection) : collect(),
            ]));
    }
}
