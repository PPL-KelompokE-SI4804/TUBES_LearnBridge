<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm() { return view('login'); }
    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($credentials, $request->boolean('remember'))) return back()->withErrors(['email'=>'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        $request->session()->regenerate(); return redirect()->intended('/dashboard');
    }
    public function registerForm() { return view('register'); }
    public function register(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:120','email'=>'required|email|unique:users,email','password'=>'required|string|min:8|confirmed','role'=>'required|in:pelajar,institusi']);
        $user = User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password']),'role'=>$data['role']]);
        if ($data['role'] === 'institusi') DB::table('institutions')->insert(['user_id'=>$user->id,'name'=>$data['name'],'verification_status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        Auth::login($user); $request->session()->regenerate(); return redirect('/dashboard')->with('success','Akun berhasil dibuat. Institusi menunggu verifikasi admin.');
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login'); }
}
