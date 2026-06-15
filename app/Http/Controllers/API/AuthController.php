<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OnboardingProgress;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);


        // Step 1: Create the main account in the users table and get the new user's ID
        $user = User::create($validatedData);

        // Step 2: Create a blank profile for this user, linked by their new ID
        StudentProfile::create([
            'user_id' => $user->id,
        ]);

        // Step 3: Start a new onboarding checklist tracker for this user
        OnboardingProgress::create([
            'user_id' => $user->id,
        ]);

        // Return a response to frontend
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user], 201);
    }
}
