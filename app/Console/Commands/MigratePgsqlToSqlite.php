<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\table;

/**
 * One-off pgsql -> sqlite migration written 2026-05-22.
 * Safe to keep in the repo as an artifact; not part of normal workflow.
 */
class MigratePgsqlToSqlite extends Command
{
    protected $signature = 'db:migrate-pgsql-to-sqlite {--force : Skip confirmation and the local-env guard}';

    protected $description = 'One-off: copy data from the legacy pgsql connection into the sqlite database.';

    /**
     * Tables to copy, in FK dependency order.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'album_lists',
        'albums',
        'album_album_list',
        'personal_access_tokens',
    ];

    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $this->guardEnvironment();

        if (! $this->option('force') && ! confirm('This will wipe database/database.sqlite and copy data from pgsql_legacy. Continue?', default: false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $this->resetSqliteDatabase();

        $summary = [];

        foreach (self::TABLES as $table) {
            $summary[] = $this->copyTable($table);
        }

        table(['Table', 'Source rows', 'Destination rows'], $summary);

        $this->info('Migration complete.');

        return self::SUCCESS;
    }

    private function guardEnvironment(): void
    {
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('DB_CONNECTION must be sqlite before running this command.');
        }

        if (! config('database.connections.pgsql_legacy')) {
            throw new RuntimeException('The pgsql_legacy connection is not configured.');
        }

        if (! $this->option('force') && ! app()->environment('local')) {
            throw new RuntimeException('Refusing to run outside the local environment. Pass --force to override.');
        }
    }

    private function resetSqliteDatabase(): void
    {
        $path = config('database.connections.sqlite.database');

        if (! is_string($path) || $path === ':memory:') {
            throw new RuntimeException('Sqlite database path is not a writable file.');
        }

        if (file_exists($path)) {
            unlink($path);
        }

        touch($path);

        DB::purge('sqlite');

        $this->info("Recreated {$path}, running migrations...");

        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true], $this->output);
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function copyTable(string $table): array
    {
        $source = DB::connection('pgsql_legacy');
        $destination = DB::connection('sqlite');

        $sourceCount = (int) $source->table($table)->count();

        if ($sourceCount === 0) {
            $this->line("  {$table}: 0 rows, skipping");

            return [$table, 0, 0];
        }

        $this->line("  {$table}: copying {$sourceCount} rows");

        $source->table($table)->orderBy('id')->chunk(self::CHUNK_SIZE, function ($rows) use ($table, $destination) {
            $payload = $rows->map(fn ($row) => (array) $row)->all();

            $destination->table($table)->insert($payload);
        });

        $destinationCount = (int) $destination->table($table)->count();

        if ($sourceCount !== $destinationCount) {
            throw new RuntimeException(
                "Row count mismatch for {$table}: pgsql={$sourceCount}, sqlite={$destinationCount}"
            );
        }

        return [$table, $sourceCount, $destinationCount];
    }
}
