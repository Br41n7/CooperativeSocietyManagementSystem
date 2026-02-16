<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public static function getDefaultRoles()
    {
        return [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'members.*',
                    'loans.*',
                    'savings.*',
                    'reports.*',
                    'settings.*',
                    'documents.*',
                    'meetings.*',
                    'users.*',
                ],
            ],
            [
                'name' => 'chairman',
                'display_name' => 'Chairman',
                'description' => 'Chairman with loan and member approval powers',
                'permissions' => [
                    'members.approve',
                    'members.view',
                    'members.edit',
                    'loans.approve',
                    'loans.view',
                    'loans.reject',
                    'reports.view',
                    'meetings.*',
                    'announcements.*',
                ],
            ],
            [
                'name' => 'secretary',
                'display_name' => 'Secretary',
                'description' => 'Secretary with document and meeting management',
                'permissions' => [
                    'members.approve',
                    'members.view',
                    'loans.approve',
                    'loans.view',
                    'documents.*',
                    'meetings.*',
                    'reports.view',
                    'announcements.*',
                ],
            ],
            [
                'name' => 'treasurer',
                'display_name' => 'Treasurer',
                'description' => 'Treasurer with financial management powers',
                'permissions' => [
                    'members.view',
                    'loans.approve',
                    'loans.view',
                    'loans.disburse',
                    'savings.*',
                    'reports.*',
                    'transactions.view',
                    'transactions.create',
                ],
            ],
            [
                'name' => 'member',
                'display_name' => 'Member',
                'description' => 'Regular member with limited access',
                'permissions' => [
                    'profile.view',
                    'profile.edit',
                    'savings.view',
                    'savings.create',
                    'loans.apply',
                    'loans.view_own',
                    'documents.view',
                    'meetings.view',
                    'notifications.view',
                ],
            ],
        ];
    }
}