<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
            'next_of_kin_name' => 'required|string|max:200',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $memberNumber = Member::generateMemberNumber();

        $member = Member::create([
            'member_number' => $memberNumber,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'occupation' => $request->occupation,
            'monthly_income' => $request->monthly_income,
            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_relationship' => $request->next_of_kin_relationship,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name' => $request->bank_account_name,
            'status' => 'pending',
        ]);

        $user = User::create([
            'member_id' => $member->id,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 5,
            'is_active' => false,
        ]);

        ActivityLog::log(null, 'member_registration', "New member registration: {$member->member_number}", $member);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Your account is pending approval.',
            'data' => [
                'member_number' => $memberNumber,
                'user_id' => $user->id,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active. Please contact admin.',
            ], 403);
        }

        $user->last_login_at = now();
        $user->save();

        ActivityLog::log($user->id, 'login', 'User logged in', $user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'permissions' => $user->role->permissions,
                ],
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        ActivityLog::log($user->id, 'logout', 'User logged out', $user);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('member', 'role');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'permissions' => $user->role->permissions,
                ],
                'member' => $user->member,
            ]
        ]);
    }
}