<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixEncoding extends Command
{
    protected $signature = 'app:fix-encoding {--dry-run : Preview changes without saving}';
    protected $description = 'Fix CP437-garbled UTF-8 text in the database';

    // CP437 corruption marker: these chars appear in garbled French text
    private const GARBLED_MARKERS = ['├', '┤', '╠', '╣', 'Ô', 'ö', '¿', '®', '¼', '½'];

    private int $fixed = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $tables = [
            ['table' => 'medical_alerts',   'pk' => 'id', 'columns' => ['description']],
            ['table' => 'catalog_acts',      'pk' => 'id', 'columns' => ['name']],
            ['table' => 'consultations',     'pk' => 'id', 'columns' => ['notes']],
            ['table' => 'appointments',      'pk' => 'id', 'columns' => ['notes']],
            ['table' => 'consultation_acts', 'pk' => 'id', 'columns' => ['notes']],
        ];

        foreach ($tables as $def) {
            $this->fixTable($def['table'], $def['pk'], $def['columns'], $dryRun);
        }

        $this->info("Done. Fixed: {$this->fixed} | Already clean: {$this->skipped}");

        return self::SUCCESS;
    }

    private function fixTable(string $table, string $pk, array $columns, bool $dryRun): void
    {
        $rows = DB::table($table)->select(array_merge([$pk], $columns))->get();

        foreach ($rows as $row) {
            $updates = [];

            foreach ($columns as $col) {
                $value = $row->$col;
                if ($value === null) {
                    continue;
                }

                if (!$this->isGarbled($value)) {
                    $this->skipped++;
                    continue;
                }

                $fixed = $this->reverseCP437($value);

                $this->line(
                    sprintf('[%s #%s] %s:', $table, $row->$pk, $col) . "\n" .
                    "  BEFORE: {$value}\n" .
                    "  AFTER:  {$fixed}"
                );

                $updates[$col] = $fixed;
                $this->fixed++;
            }

            if (!empty($updates) && !$dryRun) {
                DB::table($table)->where($pk, $row->$pk)->update($updates);
            }
        }
    }

    private function isGarbled(string $text): bool
    {
        foreach (self::GARBLED_MARKERS as $marker) {
            if (str_contains($text, $marker)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reverse CP437 corruption:
     * Each Unicode char was originally a raw CP437 byte.
     * Convert back: garbled UTF-8 → CP437 bytes (= original UTF-8 bytes).
     */
    private function reverseCP437(string $garbled): string
    {
        // iconv converts each Unicode char back to its CP437 byte value.
        // Those bytes are the original UTF-8 bytes.
        $bytes = iconv('UTF-8', 'CP850//IGNORE', $garbled);
        return $bytes !== false ? $bytes : $garbled;
    }
}
