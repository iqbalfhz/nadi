@php
    use Filament\Support\Facades\FilamentAsset;
    use Guava\Calendar\Enums\Context;
    use Filament\Support\Facades\FilamentColor;
    use Filament\Support\View\Components\ButtonComponent;
    use Carbon\CarbonImmutable;
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :after-header="$this->getCachedHeaderActionsComponent()"
        :footer="$this->getCachedFooterActionsComponent()"
    >
        <style>
            .ec-event.ec-preview,
            .ec-now-indicator {
                z-index: 30;
            }
        </style>

        @if($heading = $this->getHeading())
            <x-slot name="heading">
                {{ $this->getHeading() }}
            </x-slot>
        @endif

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <div class="flex w-full flex-col gap-4 lg:w-64 lg:shrink-0">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="areaId">
                        <option value="">Semua Lokasi</option>
                        @foreach ($this->getAreas() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <div class="mb-2 flex items-center justify-between">
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-left"
                            wire:click="miniCalendarPreviousMonth"
                            label="Bulan sebelumnya"
                            size="sm"
                        />
                        <span class="text-sm font-medium">
                            {{ CarbonImmutable::parse($this->miniCalendarMonth)->format('F Y') }}
                        </span>
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-right"
                            wire:click="miniCalendarNextMonth"
                            label="Bulan berikutnya"
                            size="sm"
                        />
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-500 dark:text-gray-400">
                        <span>Sn</span>
                        <span>Sl</span>
                        <span>Rb</span>
                        <span>Km</span>
                        <span>Jm</span>
                        <span>Sb</span>
                        <span>Mg</span>
                    </div>

                    @foreach ($this->getMiniCalendarWeeks() as $week)
                        <div class="grid grid-cols-7 gap-1">
                            @foreach ($week as $day)
                                @php
                                    $inMonth = $day->format('Y-m') === CarbonImmutable::parse($this->miniCalendarMonth)->format('Y-m');
                                    $isSelected = $day->toDateString() === $this->selectedDate;
                                    $isToday = $day->isToday();
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectMiniCalendarDate('{{ $day->toDateString() }}')"
                                    @class([
                                        'rounded-md py-1 text-xs transition-colors',
                                        'text-gray-400 dark:text-gray-600' => ! $inMonth,
                                        'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5' => $inMonth && ! $isSelected,
                                        'bg-primary-600 text-white' => $isSelected,
                                        'font-semibold ring-1 ring-primary-500' => $isToday && ! $isSelected,
                                    ])
                                >
                                    {{ $day->day }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <div
                    wire:ignore
                    data-calendar-widget
                    x-load
                    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('calendar', 'guava/calendar') }}"
                    x-data="calendar({
                        view: @js($this->getCalendarView()),
                        locale: @js($this->getLocale()),
                        firstDay: @js($this->getFirstDay()),
                        dayMaxEvents: @js($this->getDayMaxEvents()),
                        eventContent: @js($this->getEventContentJs()),
                        eventClickEnabled: @js($this->isEventClickEnabled()),
                        eventDragEnabled: @js($this->isEventDragEnabled()),
                        eventResizeEnabled: @js($this->isEventResizeEnabled()),
                        noEventsClickEnabled: @js($this->isNoEventsClickEnabled()),
                        dateClickEnabled: @js($this->isDateClickEnabled()),
                        dateSelectEnabled: @js($this->isDateSelectEnabled()),
                        datesSetEnabled: @js($this->isDatesSetEnabled()),
                        viewDidMountEnabled: @js($this->isViewDidMountEnabled()),
                        eventAllUpdatedEnabled: @js($this->isEventAllUpdatedEnabled()),
                        hasDateClickContextMenu: @js($this->hasContextMenu(Context::DateClick)),
                        hasDateSelectContextMenu: @js($this->hasContextMenu(Context::DateSelect)),
                        hasEventClickContextMenu: @js($this->hasContextMenu(Context::EventClick)),
                        hasNoEventsClickContextMenu: @js($this->hasContextMenu(Context::NoEventsClick)),
                        resources: @js($this->getResourcesJs()),
                        resourceLabelContent: @js($this->getResourceLabelContentJs()),
                        theme: @js($this->getTheme()),
                        options: @js($this->getOptions()),
                        eventAssetUrl: @js(FilamentAsset::getAlpineComponentSrc('calendar-event', 'guava/calendar')),
                    })"
                    @class(FilamentColor::getComponentClasses(ButtonComponent::class, 'primary'))
                >
                    <div data-calendar></div>
                    @if($this->hasContextMenu())
                        <x-guava-calendar::context-menu/>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
    <x-filament-actions::modals/>
</x-filament-widgets::widget>
