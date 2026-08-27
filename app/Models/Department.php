<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_department_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Every user in this department plus every user in its direct
     * sub-departments. Only two levels are supported (a sub-department
     * can't itself have children), so this doesn't need to recurse.
     */
    public function allUsers()
    {
        $departmentIds = $this->children()->pluck('id')->push($this->id);

        return User::whereIn('department_id', $departmentIds);
    }
}