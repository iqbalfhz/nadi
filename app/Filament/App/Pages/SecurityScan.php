<?php

namespace App\Filament\App\Pages;

use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrol;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class SecurityScan extends Page
{
    use HasPageShield;

    protected static string $routePath = '/security-scan/{code}';

    // Reached only via QR scan, never from the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Lapor Patroli';

    public ?SecurityCheckpoint $checkpoint = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(string $code): void
    {
        $this->checkpoint = SecurityCheckpoint::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($this->checkpoint) {
            $this->form->fill();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('photos')
                    ->label('Foto')
                    ->helperText('Ambil langsung dari kamera atau pilih dari galeri. Bisa lebih dari satu.')
                    ->collection('photos')
                    ->image()
                    ->multiple()
                    ->maxSize(10240)
                    ->required(),
                Textarea::make('incident_report')
                    ->label('Laporan Kejadian')
                    ->helperText('Kosongkan kalau tidak ada temuan.')
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->checkpoint) {
            return $schema->components([
                Text::make('QR ini tidak valid, atau titik patroli sudah dinonaktifkan. Hubungi admin kalau ini seharusnya masih berlaku.')
                    ->color('danger'),
            ]);
        }

        return $schema->components([
            Text::make("Titik: {$this->checkpoint->name}")
                ->weight('bold')
                ->size('lg'),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('submit')
                ->footer([
                    Actions::make([
                        Action::make('submit')
                            ->label('Kirim Laporan')
                            ->submit('form'),
                    ]),
                ]),
        ]);
    }

    public function submit(): void
    {
        if (! $this->checkpoint) {
            return;
        }

        $data = $this->form->getState();

        $patrol = SecurityPatrol::create([
            'security_checkpoint_id' => $this->checkpoint->id,
            'user_id' => Auth::id(),
            'incident_report' => $data['incident_report'] ?? null,
        ]);

        $this->form->model($patrol)->saveRelationships();

        Notification::make()
            ->success()
            ->title('Patroli berhasil dicatat')
            ->send();

        $this->form->fill();
    }
}
