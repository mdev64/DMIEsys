<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mattiverse\Userstamps\Traits\Userstamps;

class MaintenanceRecord extends Model
{
    use HasFactory, Userstamps;

    protected $guarded = [];

    public function scopeOpen(Builder $query): void
    {
        $query->where('comments', null);
    }

    public function scopeTodayOpen(Builder $query): void
    {
        $query->where('due_date', today()->toDateString())->where('comments', null);
    }

    public function hasMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }

    public function hasTask(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'task_id', 'id');
    }

    public function getUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
