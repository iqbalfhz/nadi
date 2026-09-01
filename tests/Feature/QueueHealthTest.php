<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageTelegramSettings;
use App\Support\QueueHealth;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * These guard the answer to "why did my report never arrive?".
 *
 * Before this existed, a crash-looping queue worker was completely invisible
 * from inside the application: reports saved, the screen went green, and
 * nothing was ever sent. The only way to find out was reading container logs.
 */
class QueueHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml runs the suite on the sync driver so jobs execute inline.
        // Production runs on the database driver, which is the only one whose
        // backlog can be counted \u2014 so that is what these assert against.
        config(['queue.default' => 'database']);
    }

    /**
     * jobs.created_at is a unix timestamp column rather than a datetime, so
     * these are written directly instead of through a factory.
     */
    private function queueJob(int $secondsAgo): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->getTimestamp(),
            'created_at' => now()->subSeconds($secondsAgo)->getTimestamp(),
        ]);
    }

    public function test_an_empty_queue_reads_as_healthy(): void
    {
        $queue = QueueHealth::read();

        $this->assertTrue($queue->readable);
        $this->assertSame(0, $queue->pending);
        $this->assertFalse($queue->isStalled());
        $this->assertSame('success', $queue->color());
        $this->assertStringContainsString('bersih', $queue->summary());
    }

    public function test_a_job_queued_moments_ago_is_not_treated_as_stuck(): void
    {
        $this->queueJob(secondsAgo: 5);

        $queue = QueueHealth::read();

        $this->assertSame(1, $queue->pending);
        $this->assertFalse($queue->isStalled(), 'A fresh job is normal latency, not a fault.');
        $this->assertSame('info', $queue->color());
    }

    /**
     * The case that matters: work waiting far longer than any worker would
     * take means no worker is taking it.
     */
    public function test_a_long_waiting_job_is_reported_as_stalled(): void
    {
        $this->queueJob(secondsAgo: 900);

        $queue = QueueHealth::read();

        $this->assertTrue($queue->isStalled());
        $this->assertSame('warning', $queue->color());
        $this->assertStringContainsString('tertahan', $queue->summary());
        $this->assertStringContainsString('queue', $queue->summary(), 'The message should name the container to check.');
    }

    public function test_failed_jobs_outrank_everything_else(): void
    {
        $this->queueJob(secondsAgo: 5);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) str()->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Telegram menolak sendMessage.',
            'failed_at' => now(),
        ]);

        $queue = QueueHealth::read();

        $this->assertSame(1, $queue->failed);
        $this->assertSame('danger', $queue->color());
        $this->assertStringContainsString('gagal', $queue->summary());
        // Reassurance matters here: the inspection itself is never lost.
        $this->assertStringContainsString('tetap tersimpan', $queue->summary());
    }

    /**
     * On any other driver the counts would be guesses, so the page says it
     * cannot tell rather than showing a confident zero.
     */
    public function test_a_non_database_queue_reports_that_it_cannot_be_read(): void
    {
        config(['queue.default' => 'sync']);

        $queue = QueueHealth::read();

        $this->assertFalse($queue->readable);
        $this->assertSame('gray', $queue->color());
        $this->assertStringContainsString('tidak bisa dibaca', $queue->summary());
    }

    public function test_the_settings_page_shows_the_queue_state(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        Livewire::test(ManageTelegramSettings::class)
            ->assertOk()
            ->assertSee('Status Antrean')
            ->assertSee('Antrean bersih');
    }

    /**
     * The buttons only appear when they would do something — an admin looking
     * at a healthy queue should not be offered a fix for a problem they do not
     * have.
     */
    public function test_the_repair_buttons_stay_hidden_while_the_queue_is_clean(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        Livewire::test(ManageTelegramSettings::class)
            ->assertActionHidden('drain')
            ->assertActionHidden('retry');
    }

    public function test_the_process_now_button_appears_once_work_is_waiting(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAsSuperAdmin();

        $this->queueJob(secondsAgo: 900);

        Livewire::test(ManageTelegramSettings::class)
            ->assertActionVisible('drain')
            ->assertSee('tertahan');
    }
}
