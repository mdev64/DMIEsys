<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostgraduateRegistration extends Model
{
    use HasFactory;

    /** @var string */
    protected $primaryKey = 'nic';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $keyType = 'string';

    /** @var string[] */
    protected $guarded = [];

    /** @var string[] */
    protected $fillable = [
        'nic',
        'applied_degree',
        'degree_cat',
        'fname',
        'lname',
        'fullname',
        'email',
        'phone',
        'birthday',
        'address',
        'gender',
        'employment',
        'noofuniversities',
        'noofcompanies',
        'noofmemberships',
        'year',
        'file_path',
        'r1_is_submit',
        'r2_is_submit',
        'ip',
        'random_phase',
    ];

    public function getCompany(): HasMany
    {
        return $this->hasMany(PGRCompany::class, 'nic', 'nic');
    }

    public function getUniversity(): HasMany
    {
        return $this->hasMany(PGRUniversity::class, 'nic', 'nic');
    }

    public function getMembership(): HasMany
    {
        return $this->hasMany(PGRMembership::class, 'nic', 'nic');
    }

    public function getReferee(): HasMany
    {
        return $this->hasMany(PGRReferee::class, 'nic', 'nic');
    }
}
