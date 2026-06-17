<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginRequest;
use App\Mail\SendOtpMail;
use App\Models\OnboardingProgress;
use App\Models\OTPVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

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

    //Verification Enpoint Logic
    public function verifyOTP(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255',
            'otp_code' => 'required|string|max:6',
        ]);

        // Find the user by email
        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Find the OTP record for this user
        $otpRecord = OTPVerification::where('user_id', $user->id)
            ->where('otp_code', $validatedData['otp_code'])
            ->latest() // Get the latest OTP record for this user and code
             ->first();
            // ->where('expires_at', '>', now())
           

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 400);
        }

        if ($otpRecord->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired.',
            ], 400);
        }
        // -----------------------------
        // Mark the user's email as verified
        $user->email_verified_at = now();
        $user->save();



        // Delete the OTP record after successful verification
        $otpRecord->delete();

        // Generates a secure API access token for the authenticated user to validate future requests.
       $token = $user->createToken('student_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'current_step' => 'profile_completion',
                'access_token' => $token,       //Frontend grabs this...
                'token_type'   => 'Bearer',      //...and attaches it as a Bearer header
                "user" => [
                    "email" => $user->email,
                    "is_email_verified" => $user->email_verified_at !== null ? true : false,
                    "verification_time" => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
                ]
            ]
        ], 200);
    }
    /**
     * User Login Endpoint
     */
    public function login(LoginRequest $request)
    {
        // 1. Find the user by email
        $user = User::where('email', $request->email)->first();

        // If user doesn't exist, stop and return an error
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // 2. Check if the password typed matches the hashed password in the database
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // 3. Security Guard: Block users who haven't verified their email via OTP yet
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Your email address is not verified. Please verify using OTP first.',
                'data' => [
                    'current_step' => 'otp_verification'
                ]
            ], 403); // 403 Forbidden
        }

        // 4. Success! Create a secure Sanctum token for this login session
        // Creates a secure login session token to pass back to the frontend application.
        $token = $user->createToken('student_auth_token')->plainTextToken;

        // 5. Return the token and user data back to the frontend payload
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user' => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                ]
            ]
        ], 200);
    }
    public function generateOTP(): string
    {
        return strval(rand(100000, 999999)); // Generate a random 6-digit OTP
    }
}
