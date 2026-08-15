<?php

namespace App\Domain\Import;

/**
 * Tallies row counts per table during a legacy import — how many rows the
 * source table had, how many were actually imported, and why any were
 * skipped — so `import:legacy` can print a before/after summary.
 */
class ImportReport
{
    /** @var array<string, int> */
    private array $sourceCounts = [];

    /** @var array<string, int> */
    private array $importedCounts = [];

    /** @var array<string, list<string>> */
    private array $skippedReasons = [];

    public function source(string $table, int $count): void
    {
        $this->sourceCounts[$table] = $count;
    }

    public function imported(string $table): void
    {
        $this->importedCounts[$table] = ($this->importedCounts[$table] ?? 0) + 1;
    }

    public function skipped(string $table, string $reason): void
    {
        $this->skippedReasons[$table][] = $reason;
    }

    /**
     * @return array<int, array{table: string, source: int, imported: int, skipped: int}>
     */
    public function rows(): array
    {
        $tables = array_unique([...array_keys($this->sourceCounts), ...array_keys($this->importedCounts)]);

        return collect($tables)
            ->map(fn (string $table) => [
                'table' => $table,
                'source' => $this->sourceCounts[$table] ?? 0,
                'imported' => $this->importedCounts[$table] ?? 0,
                'skipped' => count($this->skippedReasons[$table] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public function skippedReasons(): array
    {
        return $this->skippedReasons;
    }
}
