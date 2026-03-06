<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TrustedDevice;
use App\Mail\OtpMail;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Credenciales incorrectas', 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $fingerprint = $this->getFingerprint($request);

        // Check for trusted device
        $isTrusted = TrustedDevice::where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($isTrusted) {
            $token = $user->createToken('auth_token')->plainTextToken;
            return $this->success([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Inicio de sesión exitoso');
        }

        // Generate and send OTP
        $otp = (string) random_int(100000, 999999);
        Cache::put('otp_' . $user->id, [
            'code' => $otp,
            'fingerprint' => $fingerprint
        ], now()->addMinutes(10));

        Mail::to($user->email)->send(new OtpMail($otp));

        return $this->success(null, 'OTP enviado, por favor verifícalo. Revisa tu email.');
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'remember' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('Usuario no encontrado', 404);
        }

        $cachedData = Cache::get('otp_' . $user->id);

        if (!$cachedData || $cachedData['code'] !== $request->otp) {
            return $this->error('OTP inválido o expirado', 422);
        }

        // Clean up OTP
        Cache::forget('otp_' . $user->id);

        // Register trusted device if requested
        if ($request->remember) {
            TrustedDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'fingerprint' => $cachedData['fingerprint'],
                ],
                [
                    'name' => 'Dispositivo de confianza',
                    'expires_at' => now()->addDays(30),
                ]
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Verificación exitosa, bienvenido.');
    }

    private function getFingerprint(Request $request)
    {
        return hash('sha256', $request->userAgent() . '|' . $request->ip());
    }
}
