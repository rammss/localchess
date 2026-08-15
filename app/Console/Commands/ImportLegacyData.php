<?php

namespace App\Console\Commands;

use App\Domain\Import\LegacyImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time import of a *copy* of the LocalChess 1.0 database (see Fase 6 of
 * the rebuild plan). Configure the `legacy` connection in .env (LEGACY_DB_*)
 * to point at that copy — never at the live 1.0 database — before running.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy
        {--owner-email= : Email of the (already registered) 2.0 user who becomes Owner of every imported federation}';

    protected $description = 'Import federations, players, rankings, tournaments and competitions from a copy of the LocalChess 1.0 database';

    public function handle(LegacyImporter $importer): int
    {
        $ownerEmail = $this->option('owner-email') ?: $this->ask('Email of the user who will own every imported federation');

        if (! $ownerEmail) {
            $this->error('An --owner-email is required.');

            return self::FAILURE;
        }

        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error("Could not connect to the 'legacy' database connection: {$e->getMessage()}");
            $this->line('Check LEGACY_DB_* in your .env file — it must point at a COPY of the 1.0 database, not the live one.');

            return self::FAILURE;
        }

        if (! $this->confirm("This will import legacy data into the CURRENT database ({$this->currentDatabaseName()}). Continue?")) {
            return self::SUCCESS;
        }

        $this->info('Importing…');

        $report = $importer->import($ownerEmail);

        $this->newLine();
        $this->table(
            ['Table', 'Source rows', 'Imported', 'Skipped'],
            array_map(fn (array $row) => [$row['table'], $row['source'], $row['imported'], $row['skipped']], $report->rows()),
        );

        foreach ($report->skippedReasons() as $table => $reasons) {
            $this->newLine();
            $this->warn("Skipped in {$table}:");
            foreach (array_slice($reasons, 0, 20) as $reason) {
                $this->line("  - {$reason}");
            }
            if (count($reasons) > 20) {
                $this->line('  … and '.(count($reasons) - 20).' more');
            }
        }

        $this->newLine();
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function currentDatabaseName(): string
    {
        return DB::connection()->getDatabaseName();
    }
}
