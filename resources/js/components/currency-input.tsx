import { useState } from 'react';
import { Input } from '@/components/ui/input';

type CurrencyInputProps = {
    name: string;
    id?: string;
    placeholder?: string;
    defaultValue?: number | string;
    required?: boolean;
};

/**
 * Displays a Rupiah-formatted amount (e.g. "2.000.000") while submitting the
 * raw numeric value through a hidden field, so 20 ribu vs 2 juta is unambiguous.
 */
export function CurrencyInput({
    name,
    id,
    placeholder,
    defaultValue,
    required,
}: CurrencyInputProps) {
    // Parse numerically so decimal strings like "10000000.00" don't become
    // "1000000000" after a naive non-digit strip.
    const initial =
        defaultValue !== undefined &&
        defaultValue !== '' &&
        Number.isFinite(Number(defaultValue))
            ? String(Math.round(Number(defaultValue)))
            : '';
    const [digits, setDigits] = useState(initial);
    const display = digits ? Number(digits).toLocaleString('id-ID') : '';

    return (
        <div className="relative">
            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                Rp
            </span>
            <Input
                id={id}
                inputMode="numeric"
                className="pl-9"
                placeholder={placeholder}
                required={required}
                value={display}
                onChange={(event) =>
                    setDigits(event.target.value.replace(/\D/g, ''))
                }
            />
            <input type="hidden" name={name} value={digits} />
        </div>
    );
}
