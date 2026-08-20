<?php

namespace Tests\Feature;

use App\Enums\BarcodeFormat;
use App\Filament\App\Pages\GenerateBarcode;
use App\Filament\App\Resources\Barcodes\Pages\ListBarcodes;
use App\Models\Barcode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GenerateBarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_an_employee_can_generate_a_qr_code(): void
    {
        $user = $this->actingAsEmployeeWithPermissions('View:GenerateBarcode');

        Livewire::test(GenerateBarcode::class)
            ->set('format', BarcodeFormat::Qr->value)
            ->set('content', 'https://nadi.iqbalfhz.my.id')
            ->call('generate');

        $barcode = Barcode::query()->where('content', 'https://nadi.iqbalfhz.my.id')->firstOrFail();

        $this->assertSame(BarcodeFormat::Qr, $barcode->format);
        $this->assertSame($user->id, $barcode->created_by);
    }

    public function test_an_employee_can_generate_a_code128_barcode(): void
    {
        $this->actingAsEmployeeWithPermissions('View:GenerateBarcode');

        Livewire::test(GenerateBarcode::class)
            ->set('format', BarcodeFormat::Code128->value)
            ->set('content', 'ASET-00123')
            ->call('generate');

        $this->assertDatabaseHas('barcodes', [
            'format' => BarcodeFormat::Code128->value,
            'content' => 'ASET-00123',
        ]);
    }

    public function test_invalid_content_for_the_chosen_format_is_rejected_without_saving(): void
    {
        $this->actingAsEmployeeWithPermissions('View:GenerateBarcode');

        Livewire::test(GenerateBarcode::class)
            ->set('format', BarcodeFormat::Ean13->value)
            ->set('content', 'bukan-angka')
            ->call('generate');

        $this->assertDatabaseCount('barcodes', 0);
    }

    public function test_generating_without_choosing_a_format_does_not_save(): void
    {
        $this->actingAsEmployeeWithPermissions('View:GenerateBarcode');

        Livewire::test(GenerateBarcode::class)
            ->set('content', 'sesuatu')
            ->call('generate');

        $this->assertDatabaseCount('barcodes', 0);
    }

    public function test_the_generated_image_can_be_rendered_without_error(): void
    {
        $barcode = Barcode::factory()->create([
            'format' => BarcodeFormat::Ean13,
            'content' => '590123412345',
        ]);

        $png = $barcode->renderPng();

        $this->assertNotEmpty($png);
    }

    public function test_the_app_history_only_shows_the_current_users_own_barcodes(): void
    {
        $someoneElse = User::factory()->create();
        $me = $this->actingAsEmployeeWithPermissions('ViewAny:Barcode');

        $mine = Barcode::factory()->create(['created_by' => $me->id]);
        $theirs = Barcode::factory()->create(['created_by' => $someoneElse->id]);

        Livewire::test(ListBarcodes::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }
}
