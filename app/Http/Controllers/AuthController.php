<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Inscription utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->firstName . ' ' . $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'phone' => $request->phone,
        ]);

        $token = $user->createToken('MediExpress')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
            ],
            'token' => $token
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('MediExpress')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'phone' => $user->phone ?? '',
            ],
            'token' => $token
        ]);
    }

    /**
     * Connexion Google OAuth
     */
    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_token' => 'required|string',
            'user_info' => 'required|array',
            'user_info.email' => 'required|email',
            'user_info.given_name' => 'required|string',
            'user_info.family_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation Google',
                'errors' => $validator->errors()
            ], 422);
        }

        $userInfo = $request->user_info;
        $email = $userInfo['email'];

        // Chercher ou créer l'utilisateur
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $userInfo['given_name'] . ' ' . $userInfo['family_name'],
                'email' => $email,
                'first_name' => $userInfo['given_name'],
                'last_name' => $userInfo['family_name'],
                'password' => Hash::make(Str::random(24)), // Mot de passe aléatoire
                'email_verified_at' => now(),
                'google_id' => $userInfo['id'] ?? null,
            ]);
        }

        $token = $user->createToken('MediExpress')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion Google réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
            ],
            'token' => $token
        ]);
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email non trouvé',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => $status === Password::RESET_LINK_SENT,
            'message' => $status === Password::RESET_LINK_SENT 
                ? 'Email de récupération envoyé' 
                : 'Erreur lors de l\'envoi'
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Profil utilisateur
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ]
        ]);
    }
}
