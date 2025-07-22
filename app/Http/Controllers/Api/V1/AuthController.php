<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan; 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Register user baru.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Ambil plan Free sebagai default
        $freePlan = Plan::where('name', 'Free')->first();
        if (!$freePlan) {
            return response()->json(['message' => 'Default plan not found.'], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password), // Hash password
            'plan_id' => $freePlan->id,
        ]);

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Coba login
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details.'
            ], 401);
        }

        // Ambil user yang sedang login
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Buat token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Info user yang sedang login (berdasarkan token).
     */
    public function me()
    {
        return response()->json(Auth::user());
    }

    /**
     * Logout (hapus token akses saat ini).
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['message' => 'Successfully logged out.']);
    }

    /**
     * Generate URL redirect ke Google OAuth.
     */
    public function oAuthUrl()
    {
        // getTargetUrl() ambil URL redirect tanpa melakukan redirect langsung
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    /**
     * Callback dari Google OAuth.
     */
    public function oAuthCallback(Request $request)
    {
        // Ambil data user dari Google
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Cek apakah user sudah ada berdasarkan email Google
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Update avatar jika ada perubahan
            $existingUser->update([
                'avatar' => $googleUser->avatar ?? $googleUser->getAvatar(),
            ]);

            $token = $existingUser->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'user' => $existingUser,
                'token' => $token,
            ]);
        }

        // Jika user belum ada, buat baru dengan plan Free
        $freePlan = Plan::where('name', 'Free')->first();
        if (!$freePlan) {
            return response()->json(['message' => 'Default plan not found.'], 500);
        }

        $newUser = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getNickname(),
            'email' => $googleUser->getEmail(), // <-- perbaikan: tadinya salah pakai $existingUser
            // Password random karena user OAuth tidak input password; sesuaikan kolom jika nullable
            'password' => bcrypt(Str::random(32)),
            'plan_id' => $freePlan->id,
            'avatar' => $googleUser->getAvatar(),
        ]);

        $token = $newUser->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created and logged in successfully.',
            'user' => $newUser,
            'token' => $token,
        ], 201);
    }
}
