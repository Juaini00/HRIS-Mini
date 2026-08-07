<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for the domain enums.
 *
 * Every enum using this trait must also declare `label(): string` so the
 * generated option lists carry human-readable text into the UI.
 */
trait HasOptions
{
    /**
     * All backing values, for `Rule::in()` and migrations.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Select-box options shared with the React layer.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
