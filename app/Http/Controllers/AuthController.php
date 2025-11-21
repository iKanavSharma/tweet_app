<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    //function to register
    public function register(Request $request){
        //taking data from user 
        $request->validate([
            'name'=>'required',
            'username'=>'required|unique:users',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:8',
            'location'=>'required|string'
        ]);

        //create user
        $user=User::create([
            'name'       =>$request->name,
            'username'   =>$request->username,
            'email'      =>$request->email,
            'password'   =>Hash::make($request->password),
            'location'   =>$request->location,
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'User registered successfully',
            'user'=>$user
        ]);
    }

    public function login(Request $request){
        //data from user
        $credentials=$request->only('email','password');
        //check whether user exist or not
        try{
            if(!$token=JWTAuth::attempt($credentials)){
                return response()->json([
                    'status'=>false,
                    'message'=>'Invalid Credentials'
                ],401);
            }
        }catch(JWTException $e){
            return response()->json([
                'status'=>false,
                'message'=>'Could not create token'
            ],500);
        }

        return response()->json([
            'status'=>true,
            'message'=>'Login Successful',
            'token'=>$token
        ]);
    }
}
