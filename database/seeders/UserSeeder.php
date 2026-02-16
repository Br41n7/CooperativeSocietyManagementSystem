<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $chairmanRole = Role::where('name', 'chairman')->first();
        $secretaryRole = Role::where('name', 'secretary')->first();
        $treasurerRole = Role::where('name', 'treasurer')->first();
        $memberRole = Role::where('name', 'member')->first();

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@cooperative.com',
            'password' => Hash::make('admin123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $chairmanMember = Member::create([
            'member_number' => 'MEM000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1980-05-15',
            'gender' => 'male',
            'phone' => '+2348012345678',
            'address' => '123 Main Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'occupation' => 'Business Owner',
            'monthly_income' => 500000.00,
            'next_of_kin_name' => 'Jane Doe',
            'next_of_kin_phone' => '+2348098765432',
            'next_of_kin_relationship' => 'Spouse',
            'status' => 'active',
            'membership_date' => '2020-01-01',
            'credit_score' => 100,
        ]);

        User::create([
            'member_id' => $chairmanMember->id,
            'name' => 'John Doe',
            'email' => 'chairman@cooperative.com',
            'password' => Hash::make('chairman123'),
            'role_id' => $chairmanRole->id,
            'is_active' => true,
        ]);

        $secretaryMember = Member::create([
            'member_number' => 'MEM000002',
            'first_name' => 'Mary',
            'last_name' => 'Smith',
            'date_of_birth' => '1985-08-20',
            'gender' => 'female',
            'phone' => '+2348023456789',
            'address' => '456 Oak Avenue',
            'city' => 'Abuja',
            'state' => 'FCT',
            'occupation' => 'Teacher',
            'monthly_income' => 200000.00,
            'next_of_kin_name' => 'Robert Smith',
            'next_of_kin_phone' => '+2348076543210',
            'next_of_kin_relationship' => 'Husband',
            'status' => 'active',
            'membership_date' => '2020-03-15',
            'credit_score' => 95,
        ]);

        User::create([
            'member_id' => $secretaryMember->id,
            'name' => 'Mary Smith',
            'email' => 'secretary@cooperative.com',
            'password' => Hash::make('secretary123'),
            'role_id' => $secretaryRole->id,
            'is_active' => true,
        ]);

        $treasurerMember = Member::create([
            'member_number' => 'MEM000003',
            'first_name' => 'James',
            'last_name' => 'Johnson',
            'date_of_birth' => '1978-12-10',
            'gender' => 'male',
            'phone' => '+2348034567890',
            'address' => '789 Pine Road',
            'city' => 'Port Harcourt',
            'state' => 'Rivers',
            'occupation' => 'Accountant',
            'monthly_income' => 400000.00,
            'next_of_kin_name' => 'Sarah Johnson',
            'next_of_kin_phone' => '+2348065432109',
            'next_of_kin_relationship' => 'Spouse',
            'status' => 'active',
            'membership_date' => '2020-02-01',
            'credit_score' => 100,
        ]);

        User::create([
            'member_id' => $treasurerMember->id,
            'name' => 'James Johnson',
            'email' => 'treasurer@cooperative.com',
            'password' => Hash::make('treasurer123'),
            'role_id' => $treasurerRole->id,
            'is_active' => true,
        ]);

        for ($i = 4; $i <= 10; $i++) {
            $memberNumber = sprintf('MEM%06d', $i);
            $member = Member::create([
                'member_number' => $memberNumber,
                'first_name' => 'Member',
                'last_name' => "User {$i}",
                'date_of_birth' => now()->subYears(rand(25, 50))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'male' : 'female',
                'phone' => '+2348' . rand(100000000, 999999999),
                'address' => rand(100, 999) . ' Sample Street',
                'city' => ['Lagos', 'Abuja', 'Port Harcourt', 'Kano', 'Ibadan'][rand(0, 4)],
                'state' => ['Lagos', 'FCT', 'Rivers', 'Kano', 'Oyo'][rand(0, 4)],
                'occupation' => ['Teacher', 'Doctor', 'Engineer', 'Farmer', 'Business Owner'][rand(0, 4)],
                'monthly_income' => rand(100000, 600000),
                'next_of_kin_name' => 'Next of Kin ' . $i,
                'next_of_kin_phone' => '+2349' . rand(100000000, 999999999),
                'next_of_kin_relationship' => 'Sibling',
                'status' => 'active',
                'membership_date' => now()->subMonths(rand(12, 48))->format('Y-m-d'),
                'credit_score' => rand(70, 100),
            ]);

            User::create([
                'member_id' => $member->id,
                'name' => $member->full_name,
                'email' => "member{$i}@cooperative.com",
                'password' => Hash::make('member123'),
                'role_id' => $memberRole->id,
                'is_active' => true,
            ]);
        }
    }
}