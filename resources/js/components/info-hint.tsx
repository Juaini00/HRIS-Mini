import { Info } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * Small "info" icon that reveals an explanatory tooltip on hover or focus.
 * Use next to a field label when the field's purpose isn't obvious.
 */
export function InfoHint({ text }: { text: string }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    aria-label="Informasi"
                    className="inline-flex text-muted-foreground transition-colors hover:text-foreground focus-visible:text-foreground focus-visible:outline-none"
                >
                    <Info className="size-3.5" />
                </button>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs text-xs leading-relaxed">
                {text}
            </TooltipContent>
        </Tooltip>
    );
}
