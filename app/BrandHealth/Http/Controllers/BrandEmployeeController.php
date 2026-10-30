<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Department;
use App\Models\User;
use App\BrandHealth\Models\BrandPerformance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View as Response;

class BrandEmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        return view('brand-health.employees.index', [
            'employees' => User::with('department')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Every post this employee has been credited on, as Project Manager
     * and/or Conceptor — "what has this employee done," for the Employees
     * page's detail drill-down. Kept here (rather than reusing
     * ViewsTrendController's person* endpoints) so it's gated by
     * can-access:employees instead of can-access:views-trend — a user with
     * Employees access but not Views Trend access should still see this.
     */
    public function posts(Request $request, User $employee): JsonResponse
    {
        ['role' => $role, 'monthFrom' => $monthFrom, 'monthTo' => $monthTo, 'performances' => $performances]
            = $this->filteredPerformances($request, $employee);

        $posts = $performances
            ->map(fn (BrandPerformance $performance) => [
                'id' => $performance->id,
                'account_name' => $performance->account?->name,
                'platform' => $performance->platform,
                'post_date' => $performance->post_date?->toDateString(),
                'ads' => (bool) $performance->ads,
                'views' => $performance->resolved_views !== null ? (int) $performance->resolved_views : null,
                'link' => $performance->videoLinks->first()?->url,
                'media_product_type' => $performance->ig_media_product_type,
            ])
            ->sortByDesc('post_date')
            ->values();

        return response()->json([
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'role' => $role,
            'filters' => ['month_from' => $monthFrom, 'month_to' => $monthTo],
            'as_project_manager_count' => BrandPerformance::where('project_manager_id', $employee->id)->count(),
            'as_conceptor_count' => BrandPerformance::where('conceptor_id', $employee->id)->count(),
            'posts' => $posts,
        ]);
    }

    /**
     * Monthly Median/Max/Min views + Total Reels this employee produced (as
     * PM or Conceptor), for the Employees detail drill-down's chart — same
     * shape of figures the Views Trend page reports per account, just scoped
     * to one person's credited posts instead of one account's cycles.
     */
    public function series(Request $request, User $employee): JsonResponse
    {
        ['role' => $role, 'monthFrom' => $monthFrom, 'monthTo' => $monthTo, 'performances' => $performances]
            = $this->filteredPerformances($request, $employee);

        $series = $performances
            ->filter(fn (BrandPerformance $performance) => $performance->resolved_views !== null)
            ->groupBy(fn (BrandPerformance $performance) => $performance->post_date->format('Y-m'))
            ->map(function (Collection $group, string $month) {
                $views = $group->pluck('resolved_views');

                return [
                    'month' => $month,
                    'label' => Carbon::createFromFormat('Y-m-d', "{$month}-01")->format('M Y'),
                    'median_views' => (int) round($this->median($views)),
                    'max_views' => (int) $views->max(),
                    'min_views' => (int) $views->min(),
                    'total_views' => (int) $views->sum(),
                    'post_count' => $group->count(),
                ];
            })
            ->sortKeys()
            ->values();

        return response()->json([
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'role' => $role,
            'filters' => ['month_from' => $monthFrom, 'month_to' => $monthTo],
            'series' => $series,
        ]);
    }

    /**
     * Shared query behind posts()/series(): this employee's posts (as PM or
     * Conceptor), filtered by an optional post_date month range, each with
     * resolved_views attached.
     */
    private function filteredPerformances(Request $request, User $employee): array
    {
        $role = $request->string('role')->toString();
        $role = in_array($role, ['project_manager_id', 'conceptor_id'], true) ? $role : 'project_manager_id';

        $monthFrom = $request->string('month_from')->trim()->toString() ?: null;
        $monthTo = $request->string('month_to')->trim()->toString() ?: null;

        $performances = BrandPerformance::where($role, $employee->id)
            ->with(['account:id,name', 'videoLinks', 'igSnapshots' => fn ($query) => $query->limit(1)])
            ->when($monthFrom, function ($query) use ($monthFrom, $monthTo) {
                $start = Carbon::createFromFormat('Y-m-d', "{$monthFrom}-01")->startOfMonth();
                $end = $monthTo
                    ? Carbon::createFromFormat('Y-m-d', "{$monthTo}-01")->endOfMonth()
                    : $start->copy()->endOfMonth();

                $query->whereBetween('post_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->get()
            ->map(function (BrandPerformance $performance) {
                $snapshot = $performance->igSnapshots->first();
                $performance->resolved_views = $snapshot ? ($snapshot->views ?? $snapshot->total_interactions) : $performance->total_views_h7;

                return $performance;
            });

        return [
            'role' => $role,
            'monthFrom' => $monthFrom,
            'monthTo' => $monthTo,
            'performances' => $performances,
        ];
    }

    /**
     * Median of a collection of numeric values — the middle value when
     * sorted, or the average of the two middle values for an even count.
     */
    private function median(Collection $values): ?float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $sorted[$middle];
        }

        return (float) (($sorted[$middle - 1] + $sorted[$middle]) / 2);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        User::create($data);

        return back();
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $employee->update($data);

        return back();
    }

    public function destroy(User $employee): RedirectResponse
    {
        $employee->delete();

        return back();
    }
}
