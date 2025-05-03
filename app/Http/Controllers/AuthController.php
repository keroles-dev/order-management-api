<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Get authenticated user
     *
     * @return JsonResponse
     */
    public function user()
    {
        $user = Auth::user();
        return $this->sendResponse(
            $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
            'User fetched successfully',
            200,
        );
    }

    /**
     * Register api
     *
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        // prevent xss attack
        $request->merge(['name' => strip_tags($request->name)]);
        $request->merge(['email' => strip_tags($request->email)]);

        // validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        // create user
        $input = $request->only(['name', 'email', 'password']);
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        // create token
        $success['token'] = $user->createToken('Api')->plainTextToken;
        $success['name'] = $user->name;

        return $this->sendResponse($success, 'User registered successfully.', 201);
    }

    /**
     * Login api
     *
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        // login user
        $input = $request->only(['email', 'password']);
        $user = User::where('email', $input['email'])->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            return $this->sendError('Invalid credentials.', [], 401);
        }

        // create token
        $success['token'] = $user->createToken('Api')->plainTextToken;
        $success['name'] = $user->name;

        return $this->sendResponse($success, 'User logged in successfully.');
    }
}
