<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmploymentType;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Position;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Organization master data: departments, positions, locations, employment types,
 * leave types, and public holidays.
 *
 * Each entity gets its own URL, search, sortable columns, and pagination. The shared
 * plumbing lives in one registry rather than six near-identical controllers — adding an
 * entity means adding an entry, not copying a file.
 */
class OrganizationController extends Controller
{
    /**
     * The entities this screen manages.
     *
     * `sortable` is an allow-list: a sort column arriving from the query string is only
     * honoured if it appears here, which is what stops `?sort=` becoming SQL injection.
     *
     * @return array<string, array{label: string, model: class-string<Model>, permission: string, searchable: list<string>, sortable: list<string>, default_sort: string}>
     */
    public static function entities(): array
    {
        return [
            'departments' => [
                'label' => 'Departemen',
                'model' => Department::class,
                'permission' => Permissions::DEPARTMENTS_MANAGE,
                'searchable' => ['name', 'code'],
                'sortable' => ['name', 'code', 'is_active'],
                'default_sort' => 'name',
            ],
            'positions' => [
                'label' => 'Posisi',
                'model' => Position::class,
                'permission' => Permissions::POSITIONS_MANAGE,
                'searchable' => ['name', 'code'],
                'sortable' => ['name', 'code', 'level', 'is_active'],
                'default_sort' => 'name',
            ],
            'locations' => [
                'label' => 'Lokasi',
                'model' => Location::class,
                'permission' => Permissions::LOCATIONS_MANAGE,
                'searchable' => ['name', 'code', 'city'],
                'sortable' => ['name', 'code', 'city', 'is_active'],
                'default_sort' => 'name',
            ],
            'employment-types' => [
                'label' => 'Tipe kepegawaian',
                'model' => EmploymentType::class,
                'permission' => Permissions::EMPLOYMENT_TYPES_MANAGE,
                'searchable' => ['name', 'code'],
                'sortable' => ['name', 'code', 'is_active'],
                'default_sort' => 'name',
            ],
            'leave-types' => [
                'label' => 'Jenis cuti',
                'model' => LeaveType::class,
                'permission' => Permissions::LEAVE_TYPES_MANAGE,
                'searchable' => ['name', 'code'],
                'sortable' => ['name', 'code', 'annual_quota', 'is_active'],
                'default_sort' => 'name',
            ],
            'holidays' => [
                'label' => 'Hari libur',
                'model' => Holiday::class,
                'permission' => Permissions::HOLIDAYS_MANAGE,
                'searchable' => ['name'],
                'sortable' => ['date', 'name', 'is_active'],
                'default_sort' => 'date',
            ],
        ];
    }

    public function index(Request $request, string $entity = 'departments'): Response
    {
        $meta = $this->meta($entity);
        $this->authorizeEntity($request, $meta);

        $sort = in_array($request->query('sort'), $meta['sortable'], true)
            ? (string) $request->query('sort')
            : $meta['default_sort'];
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        /** @var Builder<Model> $query */
        $query = $meta['model']::query();

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($meta, $search): void {
                foreach ($meta['searchable'] as $column) {
                    $inner->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $this->withRelations($entity, $query);

        return Inertia::render('hris/organization', [
            'entity' => $entity,
            'label' => $meta['label'],
            'entities' => $this->visibleEntities($request),
            'sortable' => $meta['sortable'],
            'records' => $query->orderBy($sort, $direction)->paginate(20)->withQueryString(),
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()->can($meta['permission']),
        ]);
    }

    public function store(Request $request, string $entity, WriteAuditLog $audit): RedirectResponse
    {
        $meta = $this->meta($entity);
        $this->authorizeEntity($request, $meta);

        $record = $meta['model']::create($request->validate($this->rules($entity)));
        $audit->handle($request, $this->auditEvent($entity, 'created'), $record);

        return back()->with('success', $meta['label'].' ditambahkan.');
    }

    public function update(Request $request, string $entity, int $id, WriteAuditLog $audit): RedirectResponse
    {
        $meta = $this->meta($entity);
        $this->authorizeEntity($request, $meta);

        $record = $meta['model']::query()->findOrFail($id);
        $before = $record->getAttributes();

        $record->update($request->validate($this->rules($entity, $id)));
        $audit->handle($request, $this->auditEvent($entity, 'updated'), $record, [
            'before' => array_intersect_key($before, $record->getChanges()),
            'after' => $record->getChanges(),
        ]);

        return back()->with('success', $meta['label'].' diperbarui.');
    }

    /**
     * @return array{label: string, model: class-string<Model>, permission: string, searchable: list<string>, sortable: list<string>, default_sort: string}
     */
    private function meta(string $entity): array
    {
        $entities = self::entities();
        abort_unless(isset($entities[$entity]), 404);

        return $entities[$entity];
    }

    /**
     * @param  array{permission: string, ...}  $meta
     */
    private function authorizeEntity(Request $request, array $meta): void
    {
        abort_unless($request->user()->can($meta['permission']), 403);
    }

    /**
     * Only the entities the current user may actually manage appear in the navigation.
     *
     * @return list<array{key: string, label: string}>
     */
    private function visibleEntities(Request $request): array
    {
        $visible = [];

        foreach (self::entities() as $key => $meta) {
            if ($request->user()->can($meta['permission'])) {
                $visible[] = ['key' => $key, 'label' => $meta['label']];
            }
        }

        return $visible;
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function withRelations(string $entity, Builder $query): void
    {
        match ($entity) {
            'departments' => $query->withCount('employees')->with('parent:id,name'),
            'positions' => $query->with('department:id,name')->withCount('employees'),
            'locations', 'employment-types' => $query->withCount('employees'),
            'holidays' => $query->with('location:id,name'),
            default => null,
        };
    }

    /**
     * Validation rules per entity. `$ignoreId` relaxes uniqueness for the record being edited.
     *
     * @return array<string, mixed>
     */
    private function rules(string $entity, ?int $ignoreId = null): array
    {
        return match ($entity) {
            'departments' => [
                'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($ignoreId)],
                'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($ignoreId)],
                'description' => ['nullable', 'string', 'max:1000'],
                // A department cannot be its own parent; deeper cycles are caught by the model.
                'parent_id' => ['nullable', 'different:__self', Rule::exists('departments', 'id')->whereNot('id', $ignoreId)],
                'is_active' => ['boolean'],
            ],
            'positions' => [
                'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
                'name' => ['required', 'string', 'max:100'],
                'code' => ['nullable', 'string', 'max:20', Rule::unique('positions', 'code')->ignore($ignoreId)],
                'description' => ['nullable', 'string', 'max:1000'],
                'level' => ['required', 'integer', 'min:1', 'max:10'],
                'min_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
                'max_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'gte:min_salary'],
                'is_active' => ['boolean'],
            ],
            'locations' => [
                'name' => ['required', 'string', 'max:100', Rule::unique('locations', 'name')->ignore($ignoreId)],
                'code' => ['nullable', 'string', 'max:20', Rule::unique('locations', 'code')->ignore($ignoreId)],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'timezone' => ['required', 'timezone'],
                'is_active' => ['boolean'],
            ],
            'employment-types' => [
                'name' => ['required', 'string', 'max:100', Rule::unique('employment_types', 'name')->ignore($ignoreId)],
                'code' => ['nullable', 'string', 'max:20', Rule::unique('employment_types', 'code')->ignore($ignoreId)],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['boolean'],
            ],
            'leave-types' => [
                'name' => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->ignore($ignoreId)],
                'code' => ['nullable', 'string', 'max:20', Rule::unique('leave_types', 'code')->ignore($ignoreId)],
                'description' => ['nullable', 'string', 'max:1000'],
                'annual_quota' => ['required', 'integer', 'min:0', 'max:365'],
                'min_notice_days' => ['required', 'integer', 'min:0', 'max:365'],
                'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
                'color' => ['required', 'string', 'max:20'],
                'is_paid' => ['boolean'],
                'requires_attachment' => ['boolean'],
                'allows_negative_balance' => ['boolean'],
                'carry_forward_enabled' => ['boolean'],
                'is_active' => ['boolean'],
            ],
            default => [
                'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($ignoreId)],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_recurring' => ['boolean'],
                'is_active' => ['boolean'],
            ],
        };
    }

    private function auditEvent(string $entity, string $action): string
    {
        return str_replace('-', '_', rtrim($entity, 's')).'.'.$action;
    }
}
