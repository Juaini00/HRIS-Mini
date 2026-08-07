<?php

namespace App\Support;

use BackedEnum;
use Stringable;

/**
 * Helpers for building CSV exports safely.
 */
final class Csv
{
    /**
     * Characters that make Excel, LibreOffice, and Sheets treat a cell as a formula.
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Neutralise spreadsheet formula injection.
     *
     * A cell beginning with one of {@see self::FORMULA_TRIGGERS} is executed on open by
     * every major spreadsheet program, which turns an exported employee name into a
     * remote-content or command-execution vector. Prefixing a single quote keeps the
     * value readable while forcing it to be treated as text.
     */
    public static function safe(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof Stringable => (string) $value,
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default => '',
        };

        if ($string === '') {
            return '';
        }

        return in_array($string[0], self::FORMULA_TRIGGERS, true) ? "'".$string : $string;
    }

    /**
     * Sanitise a whole row.
     *
     * @param  array<int|string, mixed>  $row
     * @return list<string>
     */
    public static function safeRow(array $row): array
    {
        return array_values(array_map(self::safe(...), $row));
    }
}
