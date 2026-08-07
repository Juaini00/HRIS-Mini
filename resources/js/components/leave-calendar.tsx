import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export type CalendarEntry = {
    id: number;
    start_date: string;
    end_date: string;
    days: string;
    employee?: { employee_number: string; user?: { name: string } };
    leave_type?: { name: string; color?: string };
};

export type CalendarHoliday = { id: number; date: string; name: string };

type Props = {
    entries: CalendarEntry[];
    holidays: CalendarHoliday[];
    month: string;
    scope: string;
    scopes: string[];
    departments: { id: number; name: string }[];
    leaveTypes: { id: number; name: string }[];
    /** When false the viewer only sees their own leave, so names add nothing. */
    showNames: boolean;
    filters: { department_id?: string; leave_type_id?: string };
};

const SCOPE_LABELS: Record<string, string> = {
    personal: 'Cuti saya',
    team: 'Tim saya',
    company: 'Seluruh perusahaan',
};

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

/** Local YYYY-MM-DD; `toISOString()` would shift the date across timezones. */
function toKey(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function addMonths(month: string, delta: number): string {
    const [year, m] = month.split('-').map(Number);
    const next = new Date(year, m - 1 + delta, 1);

    return toKey(next);
}

/**
 * Days of the grid, padded so the month always starts on a Monday and fills whole weeks.
 */
function buildGrid(month: string): Date[] {
    const [year, m] = month.split('-').map(Number);
    const first = new Date(year, m - 1, 1);
    // getDay(): 0 = Sunday. Shift so Monday is 0.
    const lead = (first.getDay() + 6) % 7;
    const start = new Date(year, m - 1, 1 - lead);

    return Array.from({ length: 42 }, (_, index) => {
        const day = new Date(start);
        day.setDate(start.getDate() + index);

        return day;
    });
}

export function LeaveCalendar({
    entries,
    holidays,
    month,
    scope,
    scopes,
    departments,
    leaveTypes,
    showNames,
    filters,
}: Props) {
    const grid = useMemo(() => buildGrid(month), [month]);

    /** date key -> entries covering that date, expanded from each request's range. */
    const byDate = useMemo(() => {
        const map = new Map<string, CalendarEntry[]>();

        for (const entry of entries) {
            const cursor = new Date(`${entry.start_date}T00:00:00`);
            const end = new Date(`${entry.end_date}T00:00:00`);

            while (cursor <= end) {
                const key = toKey(cursor);
                map.set(key, [...(map.get(key) ?? []), entry]);
                cursor.setDate(cursor.getDate() + 1);
            }
        }

        return map;
    }, [entries]);

    const holidayByDate = useMemo(
        () => new Map(holidays.map((h) => [h.date.slice(0, 10), h])),
        [holidays],
    );

    const monthLabel = new Date(`${month}T00:00:00`).toLocaleDateString(
        'id-ID',
        {
            month: 'long',
            year: 'numeric',
        },
    );
    const currentMonth = Number(month.split('-')[1]);
    const todayKey = toKey(new Date());

    const go = (params: Record<string, string | undefined>) =>
        router.get(
            '/leave',
            { scope, month, ...filters, ...params },
            {
                preserveScroll: true,
                preserveState: true,
                only: [
                    'calendar',
                    'calendarScope',
                    'calendarMonth',
                    'holidays',
                ],
            },
        );

    return (
        <Card>
            <CardHeader className="gap-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle className="text-base">Kalender cuti</CardTitle>
                    <div className="flex items-center gap-1">
                        <Button
                            aria-label="Bulan sebelumnya"
                            onClick={() => go({ month: addMonths(month, -1) })}
                            size="icon"
                            variant="outline"
                        >
                            <ChevronLeft />
                        </Button>
                        <span className="min-w-36 text-center text-sm font-medium">
                            {monthLabel}
                        </span>
                        <Button
                            aria-label="Bulan berikutnya"
                            onClick={() => go({ month: addMonths(month, 1) })}
                            size="icon"
                            variant="outline"
                        >
                            <ChevronRight />
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {scopes.map((option) => (
                        <Button
                            key={option}
                            onClick={() => go({ scope: option })}
                            size="sm"
                            variant={option === scope ? 'default' : 'outline'}
                        >
                            {SCOPE_LABELS[option] ?? option}
                        </Button>
                    ))}

                    {departments.length > 0 ? (
                        <select
                            aria-label="Filter departemen"
                            className="h-8 rounded-md border bg-background px-2 text-sm"
                            onChange={(event) =>
                                go({
                                    department_id:
                                        event.target.value || undefined,
                                })
                            }
                            value={filters.department_id ?? ''}
                        >
                            <option value="">Semua departemen</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    ) : null}

                    <select
                        aria-label="Filter jenis cuti"
                        className="h-8 rounded-md border bg-background px-2 text-sm"
                        onChange={(event) =>
                            go({
                                leave_type_id: event.target.value || undefined,
                            })
                        }
                        value={filters.leave_type_id ?? ''}
                    >
                        <option value="">Semua jenis cuti</option>
                        {leaveTypes.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                </div>
            </CardHeader>

            <CardContent>
                <div className="overflow-x-auto">
                    <div className="min-w-160">
                        <div className="grid grid-cols-7 gap-1 pb-1">
                            {WEEKDAYS.map((day) => (
                                <div
                                    className="px-1 text-center text-xs font-medium text-muted-foreground"
                                    key={day}
                                >
                                    {day}
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-7 gap-1">
                            {grid.map((day) => {
                                const key = toKey(day);
                                const inMonth =
                                    day.getMonth() + 1 === currentMonth;
                                const dayEntries = byDate.get(key) ?? [];
                                const holiday = holidayByDate.get(key);
                                const isWeekend =
                                    day.getDay() === 0 || day.getDay() === 6;

                                return (
                                    <div
                                        className={[
                                            'min-h-24 rounded-md border p-1.5 text-xs',
                                            inMonth ? '' : 'opacity-40',
                                            isWeekend || holiday
                                                ? 'bg-muted/50'
                                                : '',
                                            key === todayKey
                                                ? 'ring-2 ring-primary'
                                                : '',
                                        ].join(' ')}
                                        key={key}
                                    >
                                        <div className="flex items-start justify-between gap-1">
                                            <span className="font-medium tabular-nums">
                                                {day.getDate()}
                                            </span>
                                            {holiday ? (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Badge
                                                            className="px-1 py-0 text-[10px]"
                                                            variant="secondary"
                                                        >
                                                            Libur
                                                        </Badge>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        {holiday.name}
                                                    </TooltipContent>
                                                </Tooltip>
                                            ) : null}
                                        </div>

                                        <div className="mt-1 flex flex-col gap-1">
                                            {dayEntries
                                                .slice(0, 3)
                                                .map((entry) => (
                                                    <Tooltip
                                                        key={`${key}-${entry.id}`}
                                                    >
                                                        <TooltipTrigger asChild>
                                                            <div
                                                                className="truncate rounded px-1 py-0.5 text-[11px] text-white"
                                                                style={{
                                                                    background:
                                                                        entry
                                                                            .leave_type
                                                                            ?.color ??
                                                                        '#2563eb',
                                                                }}
                                                            >
                                                                {showNames
                                                                    ? (entry
                                                                          .employee
                                                                          ?.user
                                                                          ?.name ??
                                                                      entry
                                                                          .employee
                                                                          ?.employee_number)
                                                                    : entry
                                                                          .leave_type
                                                                          ?.name}
                                                            </div>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            {/* Reasons are never shown: other
                                                            people's leave reasons are private. */}
                                                            {showNames
                                                                ? `${entry.employee?.user?.name} — ${entry.leave_type?.name}`
                                                                : entry
                                                                      .leave_type
                                                                      ?.name}
                                                        </TooltipContent>
                                                    </Tooltip>
                                                ))}
                                            {dayEntries.length > 3 ? (
                                                <span className="text-[11px] text-muted-foreground">
                                                    +{dayEntries.length - 3}{' '}
                                                    lainnya
                                                </span>
                                            ) : null}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {entries.length === 0 ? (
                    <p className="pt-4 text-center text-sm text-muted-foreground">
                        Tidak ada cuti disetujui pada bulan ini.
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}
