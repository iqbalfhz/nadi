{{--
    Old vs new for one logged change. Inline styles, not Tailwind: the admin
    panel has no Vite theme of its own (same reason as media-gallery).
--}}
@php
    $changes = $activity->attribute_changes ?? [];
    $new = $changes['attributes'] ?? [];
    $old = $changes['old'] ?? [];
    $fields = array_keys($new + $old);
    $properties = collect($activity->properties ?? [])->except([])->all();
@endphp

<div style="display:flex;flex-direction:column;gap:1rem;font-size:0.875rem">
    <div style="display:grid;grid-template-columns:9rem 1fr;gap:0.5rem 1rem">
        <span style="opacity:0.65">{{ __('Waktu') }}</span>
        <span>{{ $activity->created_at?->format('d M Y, H:i:s') }}</span>

        <span style="opacity:0.65">{{ __('Pelaku') }}</span>
        <span>{{ $activity->causerUser?->name ?? __('Sistem / tidak login') }}</span>

        <span style="opacity:0.65">{{ __('Aktivitas') }}</span>
        <span>{{ $activity->description }}</span>

        @if ($subject = $activity->subjectLabel())
            <span style="opacity:0.65">{{ __('Data Terkait') }}</span>
            <span>{{ $subject }}</span>
        @endif
    </div>

    @if ($fields !== [])
        <div>
            <p style="margin:0 0 0.5rem;font-weight:600">{{ __('Perubahan Nilai') }}</p>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                    <tr style="text-align:left">
                        <th style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.3)">{{ __('Kolom') }}</th>
                        <th style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.3)">{{ __('Sebelum') }}</th>
                        <th style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.3)">{{ __('Sesudah') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($fields as $field)
                        <tr>
                            <td style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.15);font-family:ui-monospace,monospace">{{ $field }}</td>
                            <td style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.15);opacity:0.7">
                                {{ \App\Support\ActivityValue::render($old[$field] ?? null) }}
                            </td>
                            <td style="padding:0.5rem;border-bottom:1px solid rgba(128,128,128,0.15);font-weight:600">
                                {{ \App\Support\ActivityValue::render($new[$field] ?? null) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($properties !== [])
        <div>
            <p style="margin:0 0 0.5rem;font-weight:600">{{ __('Keterangan Tambahan') }}</p>
            <div style="display:grid;grid-template-columns:9rem 1fr;gap:0.5rem 1rem">
                @foreach ($properties as $key => $value)
                    <span style="opacity:0.65;font-family:ui-monospace,monospace">{{ $key }}</span>
                    <span>{{ \App\Support\ActivityValue::render($value) }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($fields === [] && $properties === [])
        <p style="margin:0;opacity:0.7">{{ __('Tidak ada rincian tambahan untuk aktivitas ini.') }}</p>
    @endif
</div>
