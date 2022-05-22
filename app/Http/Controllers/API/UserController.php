<?php

namespace App\Http\Controllers\API;

use App\Helpers\Cloudinary;
use App\Helpers\JwtHelpers;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function login(Request $request)
    {
        try {
            $rules = Validator::make($request->all(), [
                'email' => 'email|required',
                'password' => "string|required",
            ]);

            if ($rules->fails()) {
                return ResponseFormatter::error($rules->errors()->toArray(), 'Input Error');
            }

            $credential = request(['email', 'password']);

            if (!Auth::attempt($credential)) {
                throw new Exception("Invalid Credentials!", 400);
            }

            $user = User::with('profile')->where('email', $request->email)->first();

            if (!$user) {
                throw new Exception("Wrong email!", 400);
            }

            if (!Hash::check($request->password, $user->password)) {
                throw new Exception("Wrong Password!", 400);
            }

            $jwt = new JwtHelpers(env("JWT_SECRET_APP"));

            $jwtToken = $jwt->getToken($user->id);

            return ResponseFormatter::success([
                'user' => $user,
                'token' => $jwtToken,
                'token_type' => "Bearer",
            ]);
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, $err->getMessage(), 'error', $code);
        }
    }

    public function register(Request $request)
    {
        $current_public_id = null;
        $cloudinary = new Cloudinary();
        try {
            $rules = Validator::make($request->all(), [
                'name' => "string|required",
                'email' => "email|required|unique:users,email",
                'password' => "min:8|required",
                'address' => "string",
                'phone_number' => "string",
                'house_number' => "string",
                'city' => "string",
                'profile' => "image|required",
            ]);

            if ($rules->fails()) {
                return ResponseFormatter::error($rules->errors()->toArray(), 'Error input', 'error', 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $profile = [
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'house_number' => $request->house_number,
                'city' => $request->city,
                'user_id' => $user->id,
            ];

            if ($request->file('profile')) {
                $response =  $cloudinary->postImage($request->file('profile')->path(), 'foodMarketProfile');
                $profile['profile_picture'] = $response['image_url'];
                $profile['picture_public_id'] = $response['public_id'];
                $current_public_id = $response['public_id'];
            }

            UserProfile::create($profile);

            $user = User::with('profile')->find($user->id);

            $token = (new JwtHelpers(env('JWT_SECRET_APP')))->getToken($user->id);

            return ResponseFormatter::success([
                'user' => $user,
                'token' => $token,
                'token_type' => "Bearer",
            ], "created", 201);
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($current_public_id) {
                $cloudinary->deleteImage($current_public_id);
            }

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }
            Log::alert($err->getMessage());
            return ResponseFormatter::error(null, $err->getMessage(), 'error', $code);
        }
    }

    public function profile(Request $request)
    {
        $login_user = $request->attributes->get('user');

        $user = User::with('profile')->find($login_user);

        return ResponseFormatter::success($user, 'success');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            $rules = Validator::make($request->all(), [
                'name' => "string",
                'address' => "string",
                'house_number' => "string",
                "phone_number" => "string",
                'city' => "string",
                'profile' => "image"
            ]);

            if ($rules->fails()) return ResponseFormatter::error($rules->errors(), 'Input error');

            $login_user = $request->attributes->get('user');

            $user = User::with('profile')->find($login_user);

            if ($request->name) {
                $user->update([
                    'name' => $request->name
                ]);
            }

            $profile = [];

            if ($request->address) {
                $profile['address'] = $request->address;
            }

            if ($request->house_number) $profile['house_number'] = $request->house_number;

            if ($request->phone_number) $profile['phone_number'] = $request->phone_number;

            if ($request->city) $profile['city'] = $request->city;

            if ($request->file('profile')) {
                $cloudinary = new Cloudinary();

                if ($user->profile->picture_public_id) {
                    $cloudinary->deleteImage($user->profile->picture_public_id);
                }

                $res = $cloudinary->postImage($request->file('profile')->path(), 'foodMarketProfile');

                $profile['profile_picture'] = $res['image_url'];
                $profile['picture_public_id'] = $res['public_id'];
            }

            UserProfile::find($user->profile->id)->update($profile);

            $user = User::with('profile')->find($login_user);

            return ResponseFormatter::success($user, "created", 201);
        } catch (Exception $err) {
            //throw $th;
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, $err->getMessage(), 'error', $code);
        }
    }
}
