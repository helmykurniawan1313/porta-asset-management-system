<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\PerformanceReview\Models\ReviewAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'job_level',
        'joined_at',
        'resigned_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joined_at' => 'date',
            'resigned_at' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * A person's manager is their department's Head of Department. If their
     * own department has no head (the common case for a sub-department —
     * only top-level departments get a Head), this walks up to the parent
     * department's head instead, so e.g. a Finance employee's manager is
     * Operational's head, not left dangling with no manager at all. Not
     * stored directly — always derived, so it can never go stale relative
     * to Department::head_of_department_id. Returns null for someone with
     * no department, or for the Head of Department themselves (they have no
     * manager in this system).
     */
    public function manager(): ?User
    {
        $department = $this->department;

        if (! $department) {
            return null;
        }

        $headId = $department->head_of_department_id ?? $department->parent?->head_of_department_id;

        if (! $headId || $headId === $this->id) {
            return null;
        }

        return User::find($headId);
    }

    /**
     * Direct reports are every other user this person is Head of Department
     * for. For a top-level department's head, that includes everyone in
     * that department AND everyone in its sub-departments (e.g. Operational's
     * head sees Finance, IT, HR, etc.) — sub-departments don't have their
     * own head, so their staff report to the parent's head. For someone who
     * isn't a Head of Department anywhere, this is empty.
     */
    public function directReports()
    {
        $headedDepartmentIds = Department::where('head_of_department_id', $this->id)->pluck('id');

        if ($headedDepartmentIds->isEmpty()) {
            return User::whereRaw('1 = 0');
        }

        $visibleDepartmentIds = Department::where(function ($q) use ($headedDepartmentIds) {
            $q->whereIn('id', $headedDepartmentIds)
                ->orWhereIn('parent_id', $headedDepartmentIds);
        })->pluck('id');

        return User::whereIn('department_id', $visibleDepartmentIds)
            ->where('id', '!=', $this->id);
    }

    public function reviewAssignmentsAsReviewer(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewer_id');
    }

    public function reviewAssignmentsAsReviewee(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewee_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function softwareAllocations()
    {
        return $this->morphMany(SubscriptionAllocation::class, 'assignable');
    }

    /**
     * True if this user belongs to the named department, or to a
     * sub-department of it (e.g. isInDepartment('Ops') matches someone in
     * Ops itself or in Finance/HR/IT under Ops). Used to gate
     * department-flavored menu sections (e.g. the IT department seeing the
     * Inventory menu) independently of role, since role governs access
     * level while department governs which functional area's menu applies.
     */
    public function isInDepartment(string $name): bool
    {
        $department = $this->department;

        if (! $department) {
            return false;
        }

        return $department->name === $name || $department->parent?->name === $name;
    }

    /**
     * True if this user is Head of Department for the named department —
     * used e.g. to grant the Account Manager department's head a view-all
     * (but not edit) view over every client, regardless of who owns it.
     */
    public function isHeadOfDepartment(string $name): bool
    {
        return Department::where('name', $name)->where('head_of_department_id', $this->id)->exists();
    }
}
