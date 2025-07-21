<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\plan;
use App\Models\User;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;





class AuthController extends Controller
{
    public function register (Request $request){
        $validator = Validator::make($request->all(), [
            'name'=> 'required|string|max:255',
            'email'=> 'required|string|email|max:255|unique:users',
            'password'=> 'required|string|min:8',
        ]);

        if ($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        //mengambil plan free sebagai delfault saat registrasi
        $freePlan = plan::where('name', 'Free')->frist();
        if (!$freePlan) {
            return response()->json(['message' => 'Delfault plan not found.'], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'plan_id' => $freePlan->id, //Assign default plan
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'massage' => 'User created successfully',
            'user'=> $user,
            'token'=>$token
        ], 201);


    }

    public function login (Request $request){
        $validator = Validator::make($request->all(), [

            'email'=> 'required|string|email',
            'password'=> 'required|string',
        ]);

        if ($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

       if (!Auth::attempt($request->only('email','password'))){
              return response()->json([
             'message'=> 'Invalid login details'
         ], 401);
        }

        $user = User::where('email', $request->email)->first();
         $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'massage' => 'Login successfully',
            'user'=> $user,
            'token'=>$token
        ]);



    }

    public function me(){
        return response()->json(auth::user());
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'successfully loged out']);
    }

    public function oAuthUrl()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $google */
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    public function oAuthCallback(Request $request){

        $user = Socialite::driver('google')->stateless()->user();
        //dd['OAuth callback received' => $user]);
        $existingUser = User::where('email', $user->getEmail())->first();
        if ($existingUser) {
            $token = $existingUser->createToken('auth_token')->plainTextToken;
            $existingUser->update([
                'avatar' => $user->avatar ?? $user->getAvatar()
            ]);
            return response()->json([
                'message' => 'login seccessful',
                'user' => $existingUser,
                'token' =>$token,
            ]);
        } else {
            $freePlan = plan::where('name', 'Free')->first();
            if (!$freePlan){
                return response()->json(['message' => 'Delfault plan not found.'], 500);
                //dd($freePlan->id);
            }

            $newUser = User::create([
                'name' => $user->getName(),
                'email' => $existingUser,
                'password' => null,
                'plan_id' => $freePlan->id,
                'avatar' => $user->getAvatar()
            ]);

            $token = $newUser->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'User created and logged in succesddfully',
                'user' => $newUser,
                'token' =>$token,
            ], 201);
        }
    }

}

