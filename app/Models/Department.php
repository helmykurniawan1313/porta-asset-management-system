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
}