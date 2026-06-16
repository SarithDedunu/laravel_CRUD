<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OnboardingProgress;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\SendOtpMail;
use App\Models\OTPVerification;
use Illuminate\Support\Facades\Mail;

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


        // 1: Create the main account in the users table and get the new user's ID
        $user = User::create($validatedData);

        // 2. Generate OTP for email verification
        $otp = $this->generateOTP();

        // 3: Create a blank profile for this user, linked by their new ID
        StudentProfile::create([
            'user_id' => $user->id,
        ]);

        // 4: Start a new onboarding checklist tracker for this user
        OnboardingProgress::create([
            'user_id' => $user->id,
        ]);


        // 5: Store the OTP in the database with an expiration time
        OTPVerification::create([
            'user_id' => $user->id,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(4), // OTP expires in 4 minutes
        ]);

        // 6: Send the OTP to the user's email
        Mail::to($user->email)->send(new SendOtpMail($otp));

        // 7:Return a response to frontend
        return response()->json([
            'success' => true,
            'message' => 'Registration successful. OTP Sent to email.',
            'data' => [
                'current_step' => 'otp_verification'
            ]
        ], 201);
    }


    public function generateOTP(): string
    {
        return strval(rand(100000, 999999)); // Generate a random 6-digit OTP
    }
}
