<?php

namespace Tests\Feature;

use ReflectionClass;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Spatie\DbDumper\Databases\MySql;
use Tests\TestCase;

/**
 * Guards the two flags that let the nightly backup dump the database at all.
 *
 * mysqldump in the production image comes from mariadb-client, which verifies
 * the server certificate. Coolify's MySQL presents a self-signed one, so the
 * dump failed with "TLS/SSL error: self-signed certificate in certificate
 * chain" — while PHP's own connection, which does not verify, worked fine and
 * gave no hint anything was wrong.
 *
 * The pairing is easy to break by half: `skip_ssl` alone falls back to the
 * library's default `ssl-mode=DISABLED`, which is a MySQL-client option the
 * MariaDB client rejects outright. Both must survive together.
 */
class BackupDumpConfigTest extends TestCase
{
    public function test_the_mysql_dump_skips_tls_with_a_mariadb_compatible_flag(): void
    {
        $dumper = DbDumperFactory::createFromConnection('mysql');

        $this->assertInstanceOf(MySql::class, $dumper);

        $reflection = new ReflectionClass($dumper);

        $this->assertTrue(
            $reflection->getProperty('skipSsl')->getValue($dumper),
            'Without skip_ssl the dumper emits no SSL flag at all and mysqldump goes back to verifying the self-signed certificate.',
        );

        $this->assertSame(
            'skip-ssl',
            $reflection->getMethod('getSSLFlag')->invoke($dumper),
            'ssl-mode=DISABLED is a MySQL-client option; the MariaDB client in the production image rejects it as an unknown variable.',
        );
    }

    /**
     * The backup deliberately covers the Penomoran Dokumen module only, so an
     * empty table list would quietly widen it to the entire database.
     */
    public function test_the_dump_is_still_limited_to_the_numbering_module(): void
    {
        $tables = config('database.connections.mysql.dump.include_tables');

        $this->assertIsArray($tables);
        $this->assertNotEmpty($tables);
        $this->assertContains('documents', $tables);
    }
}
