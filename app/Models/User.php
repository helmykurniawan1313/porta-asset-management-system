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
        'job_title',
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
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * A person's manager is their department's Head of Department.
     * Not stored directly — always derived, so it can never go stale
     * relative to Department::head_of_department_id. Returns null for
     * someone with no department, or for the Head of Department themselves
     * (they have no manager in this system).
     */
    public function manager(): ?User
    {
        if (! $this->department_id) {
            return null;
        }

        $headId = $this->department?->head_of_department_id;

        if (! $headId || $headId === $this->id) {
            return null;
        }

        return $this->relationLoaded('department') && $this->department->relationLoaded('headOfDepartment')
            ? $this->department->headOfDepartment
            : User::find($headId);
    }

    /**
     * Direct reports are every other user in the same department whose
     * Head of Department is this user. Empty if this user isn't a
     * Head of Department.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'department_id', 'department_id')
            ->whereHas('department', fn ($q) => $q->where('head_of_department_id', $this->id))
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
}
