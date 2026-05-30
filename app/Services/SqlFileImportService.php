<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SqlFileImportService
{
    private const MAX_INSERT_BYTES = 262144;

    public function import(string $path): array
    {
        $path = $this->resolvePath($path);
        $sql = File::get($path);

        if (! is_string($sql) || trim($sql) === '') {
            throw new \InvalidArgumentException("SQL file is empty at {$path}");
        }

        $statements = $this->splitStatements($sql);
        $executedStatements = 0;

        foreach ($statements as $statement) {
            foreach ($this->expandStatement($statement) as $expandedStatement) {
                DB::unprepared($expandedStatement);
                $executedStatements++;
            }
        }

        return [
            'path' => $path,
            'statements' => $executedStatements,
        ];
    }

    private function resolvePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        $relativePath = base_path(ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));

        if (File::exists($relativePath)) {
            return $relativePath;
        }

        throw new \InvalidArgumentException("SQL file not found at {$path}");
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';

        foreach (preg_split('/\R/', $sql) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line."\n";

            if (str_ends_with($trimmed, ';')) {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';
            }
        }

        $tail = trim($buffer);

        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function expandStatement(string $statement): array
    {
        if (! preg_match('/^INSERT\s+INTO\s+`?[^`\s]+`?\s*\((.*?)\)\s*VALUES\s*(.*);$/is', $statement, $matches)) {
            return [rtrim($statement, ';').';'];
        }

        $columnList = trim($matches[1]);
        $values = trim($matches[2]);

        $rows = $this->splitInsertRows($values);

        $table = $this->extractInsertTable($statement);

        return $this->chunkInsertRowsBySize($table, $columnList, $rows);
    }

    private function splitInsertRows(string $values): array
    {
        $rows = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];
            $previous = $index > 0 ? $values[$index - 1] : null;

            if ($char === "'" && $previous !== '\\') {
                $inString = ! $inString;
            }

            if (! $inString) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                }
            }

            $buffer .= $char;

            if ($depth === 0 && ! $inString && $char === ')') {
                $rows[] = trim($buffer);
                $buffer = '';

                while ($index + 1 < $length && ctype_space($values[$index + 1])) {
                    $index++;
                }

                if ($index + 1 < $length && $values[$index + 1] === ',') {
                    $index++;
                }

                while ($index + 1 < $length && ctype_space($values[$index + 1])) {
                    $index++;
                }
            }
        }

        $tail = trim($buffer);

        if ($tail !== '') {
            $rows[] = $tail;
        }

        return $rows;
    }

    private function chunkInsertRowsBySize(string $table, string $columnList, array $rows): array
    {
        $statements = [];
        $currentRows = [];
        $currentBytes = 0;

            foreach ($rows as $row) {
            $rowBytes = strlen($row);
            $additionalBytes = $rowBytes + (empty($currentRows) ? 0 : 2);

            if (! empty($currentRows) && ($currentBytes + $additionalBytes) > self::MAX_INSERT_BYTES) {
                    $statements[] = sprintf(
                        'INSERT IGNORE INTO %s (%s) VALUES %s;',
                        $table,
                        $columnList,
                        implode(', ', $currentRows)
                    );

                $currentRows = [];
                $currentBytes = 0;
            }

            $currentRows[] = $row;
            $currentBytes += $rowBytes + (count($currentRows) > 1 ? 2 : 0);
        }

        if ($currentRows !== []) {
                $statements[] = sprintf(
                    'INSERT IGNORE INTO %s (%s) VALUES %s;',
                    $table,
                    $columnList,
                    implode(', ', $currentRows)
                );
        }

        return $statements;
    }

    private function extractInsertTable(string $statement): string
    {
        if (preg_match('/^INSERT\s+INTO\s+(`?[^`\s]+`?)/i', $statement, $matches)) {
            return $matches[1];
        }

        return 'UNKNOWN_TABLE';
    }
}
