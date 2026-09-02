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

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required'     => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min'          => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed'    => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Mật khẩu hiện tại không chính xác!');
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        \App\Models\AuditLog::logAction($user->id, 'UPDATE_PASSWORD', 'users', $user->id, null, [
            'action' => 'Người dùng tự đổi mật khẩu cá nhân'
        ]);

        return back()->with('success', 'Đổi mật khẩu thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

