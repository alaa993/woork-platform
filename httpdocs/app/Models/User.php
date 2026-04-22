<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Notifiable, Billable;

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_COMPANY_ADMIN = 'company_admin';
    const ROLE_EMPLOYEE = 'employee';

    protected $fillable = [
        'organization_id', 'name', 'email', 'phone', 'role', 'password', 'language'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isSuperAdmin() { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isCompanyAdmin() { return $this->role === self::ROLE_COMPANY_ADMIN; }
    public function isEmployee() { return $this->role === self::ROLE_EMPLOYEE; }
}
