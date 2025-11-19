<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('hr-api')->attempt($credentials)) {
            return response()->json(['status' => false, 'message' => 'Invalid credentials'], 401);
        }

        $hr = auth('hr-api')->user();

        return response()->json([
            'status' => true,
            'token'  => $token,
            'data'   => [
                'hr' => $hr,
                
            ]
        ]);
    }

    public function me()
    {
        $hr = auth('hr-api')->user();
        if (!$hr) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'hr' => $hr,
                'permissions' => $hr->is_super_admin 
                    ? ['all'] 
                    : $hr->permissions->pluck('name')
            ]
        ]);
    }

    public function logout()
    {
        auth('hr-api')->logout();
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh()
    {
        try {
            $newToken = auth('hr-api')->refresh();
            $hr = auth('hr-api')->user();

            return response()->json([
                'status' => true,
                'token' => $newToken,
                'data' => [
                    'hr' => $hr,
                  
                ]
            ]);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid'
            ], 401);
        }
    }
}
