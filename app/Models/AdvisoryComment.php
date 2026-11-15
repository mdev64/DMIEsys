<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisoryComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'comment',
        'commented_by',
        'need_attention',
        'is_handled',
        'handled_by',
    ];

    protected $hidden = [
        'comment',
    ];

    protected $casts = [
        'comment' => 'encrypted',
    ];

    public function hasStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function hasAdvisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commented_by', 'id');
    }
}
