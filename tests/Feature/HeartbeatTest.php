<?php

namespace Tests\Feature;

use App\Support\Heartbeat;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * The liveness files behind the `scheduler` and `queue` container
 * healthchecks.
 *
 * Those containers have no port to probe, and a crash already restarts them.
 * What the heartbeat catches is a process that is alive but stuck — a wedged
 * schedule:work silently stops the nightly backup. The healthcheck itself
 * lives in docker-compose.yml, so half of these tests read that file: the
 * contract between the two is a path, and a path that drifts turns a healthy
 * container red with nothing in the logs to say why.
 */
class HeartbeatTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach ([Heartbeat::SCHEDULER, Heartbeat::QUEUE] as $name) {
            @unlink(Heartbeat::path($name));
        }

        parent::tearDown();
    }

    public function test_a_beat_writes_the_file(): void
    {
        @unlink(Heartbeat::path(Heartbeat::QUEUE));

        Heartbeat::beat(Heartbeat::QUEUE);

        $this->assertFileExists(Heartbeat::path(Heartbeat::QUEUE));
    }

    /**
     * The worker fires its hook every three seconds while idle. Writing a
     * file each time would be pointless churn; the check only thinks in
     * minutes.
     */
    public function test_beats_are_throttled(): void
    {
        $path = Heartbeat::path(Heartbeat::QUEUE);
        Heartbeat::beat(Heartbeat::QUEUE);

        touch($path, now()->subSeconds(10)->getTimestamp());
        clearstatcache(true, $path);
        Heartbeat::beat(Heartbeat::QUEUE);
        clearstatcache(true, $path);

        $this->assertSame(now()->subSeconds(10)->getTimestamp(), filemtime($path), 'Ten seconds is too soon to write again.');

        touch($path, now()->subMinute()->getTimestamp());
        clearstatcache(true, $path);
        Heartbeat::beat(Heartbeat::QUEUE);
        clearstatcache(true, $path);

        $this->assertSame(now()->getTimestamp(), filemtime($path), 'A minute-old beat must be refreshed.');
    }

    public function test_the_scheduler_beats_every_minute(): void
    {
        $heartbeat = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => $event instanceof CallbackEvent && $event->description === 'Tanda hidup scheduler untuk healthcheck container');

        $this->assertNotNull($heartbeat, 'routes/console.php must schedule the scheduler heartbeat.');
        $this->assertSame('* * * * *', $heartbeat->expression);

        @unlink(Heartbeat::path(Heartbeat::SCHEDULER));
        $heartbeat->run($this->app);

        $this->assertFileExists(Heartbeat::path(Heartbeat::SCHEDULER));
    }

    public function test_the_queue_worker_beats_on_every_loop(): void
    {
        @unlink(Heartbeat::path(Heartbeat::QUEUE));

        event(new Looping('database', 'default'));

        $this->assertFileExists(Heartbeat::path(Heartbeat::QUEUE));
    }

    /**
     * The worker asks the Looping event whether to keep going, and a
     * listener that answers `false` makes it stop taking jobs. A heartbeat
     * must never be able to pause the queue it is watching.
     */
    public function test_the_heartbeat_never_pauses_the_worker(): void
    {
        $this->assertNotFalse(Event::until(new Looping('database', 'default')));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function watchedServices(): array
    {
        return [
            'scheduler' => [Heartbeat::SCHEDULER],
            'queue' => [Heartbeat::QUEUE],
        ];
    }

    /**
     * The healthcheck must read the very file the app writes. The container
     * runs from /app, so that is where storage_path() lands inside it.
     */
    #[DataProvider('watchedServices')]
    public function test_the_compose_healthcheck_reads_the_file_the_app_writes(string $service): void
    {
        $this->assertStringContainsString(
            '/app/'.$this->relativeHeartbeatPath($service),
            $this->healthcheckCode($service),
        );
    }

    /**
     * Runs the exact expression from docker-compose.yml, pointed at a
     * scratch file. A healthcheck is code like any other, and one that is
     * wrong fails in the least visible place there is.
     */
    #[DataProvider('watchedServices')]
    public function test_the_compose_healthcheck_tells_fresh_from_stale(string $service): void
    {
        $scratch = tempnam(sys_get_temp_dir(), 'heartbeat');
        $code = str_replace(
            '/app/'.$this->relativeHeartbeatPath($service),
            str_replace('\\', '/', $scratch),
            $this->healthcheckCode($service),
        );

        touch($scratch);
        $this->assertSame(0, $this->runPhp($code), 'A fresh beat must pass.');

        touch($scratch, time() - 3600);
        $this->assertSame(1, $this->runPhp($code), 'An hour-old beat must fail.');

        unlink($scratch);
        $this->assertSame(1, $this->runPhp($code), 'No beat at all must fail.');
    }

    /**
     * Compose substitutes `$name` from the environment before the container
     * ever sees the command, so a `$` in a healthcheck is a check that is
     * quietly rewritten into something else.
     */
    public function test_no_healthcheck_contains_a_dollar_sign(): void
    {
        foreach ($this->compose()['services'] as $name => $service) {
            $test = $service['healthcheck']['test'] ?? [];

            $this->assertStringNotContainsString('$', implode(' ', (array) $test), "Healthcheck for {$name}");
        }
    }

    private function relativeHeartbeatPath(string $name): string
    {
        $base = str_replace('\\', '/', base_path()).'/';

        return str_replace($base, '', str_replace('\\', '/', Heartbeat::path($name)));
    }

    private function healthcheckCode(string $service): string
    {
        $test = $this->compose()['services'][$service]['healthcheck']['test'];

        // ["CMD", "php", "-r", "<code>"]
        $this->assertSame(['CMD', 'php', '-r'], array_slice($test, 0, 3));

        return $test[3];
    }

    private function runPhp(string $code): ?int
    {
        return (new Process([PHP_BINARY, '-r', $code]))->run();
    }

    /**
     * @return array<string, mixed>
     */
    private function compose(): array
    {
        return Yaml::parseFile(base_path('docker-compose.yml'));
    }
}
