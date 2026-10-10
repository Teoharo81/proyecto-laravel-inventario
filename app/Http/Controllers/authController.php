<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\Rules\Password;
use Symfony\Contracts\Service\Attribute\Required;

class authController extends Controller
{
    public function login(Request $request){
            //validar
                $credenciales=$request->validate([
                    "email"=>"required|email|min:5|max:200",
                    "password"=>["required",Password::min(6)
                                                    ->mixedcase()
                                                    ->letters()
                                                    ->numbers()
                                                    ->symbols()
                    ]

            ]);
            //autenticar
            if(!Auth::attempt($credenciales)){
                return response()->json([
                    "mensaje"=>"credenciales incorrectos"
                ],401);
            }
            //generar token
            $token=$request->user()->createToken("TokenAuth")->plainTextToken;

            //responder el usuario +token
                    
            return response()->json([
                 "access_token"=>$token,
                 "usuario"=>$request->user()
                ]);

    }
    public function register(Request $request){

        //validacion
        $request->validate([
            "name"=>"required",
            "email"=>"required|email|unique:users",
            "password"=>"Required"
        ]);

        //registro
        $usuario=new User();
        $usuario->name=$request->name;
        $usuario->email=$request->email;
        $usuario->password=Hash::make($request->pasword);
        $usuario->save();
        //respuesta
        return response()->json(["mensaje"=>"Usuario registrado"]);
       

    }
    public function profile(Request $request){
        return $request->user();

    }
    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return response()->json(["mensaje"=>"logout"]);

    }//cerrar sesion
}
