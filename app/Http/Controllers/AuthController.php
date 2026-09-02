<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'error' => 'Tên đăng nhập hoặc mật khẩu không chính xác!',
        ])->onlyInput('username');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|min:3|max:100',
            'username'  => 'required|string|min:3|max:50|unique:users,username',
            'password'  => 'required|string|min:6|confirmed',
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'full_name.min'      => 'Họ tên phải có ít nhất 3 ký tự.',
            'username.required'  => 'Vui lòng nhập tên đăng nhập.',
            'username.min'       => 'Tên đăng nhập phải có ít nhất 3 ký tự.',
            'username.unique'    => 'Tên đăng nhập này đã tồn tại!',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::create([
            'full_name' => trim($request->full_name),
            'username'  => trim($request->username),
            'password'  => $request->password,
            'role_id'   => 4, // Mặc định: Thủ kho (Staff)
            'is_active'  => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Đăng ký thành công! Chào mừng ' . $user->full_name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
