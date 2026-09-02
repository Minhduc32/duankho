<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Models\Receipt;
use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role')->orderBy('id', 'ASC');

        if ($q = trim($request->get('q', ''))) {
            $query->where(function ($qb) use ($q) {
                $qb->where('username', 'like', "%{$q}%")
                   ->orWhere('full_name', 'like', "%{$q}%");
            });
        }

        if ($roleId = $request->get('role_id')) {
            $query->where('role_id', $roleId);
        }

        $users = $query->get();
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|alpha_dash|max:50|unique:users,username',
            'full_name' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'username'  => strtolower(trim($request->username)),
            'full_name' => trim($request->full_name),
            'password'  => Hash::make($request->password),
            'role_id'   => (int)$request->role_id,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        AuditLog::logAction(Auth::id(), 'CREATE', 'users', $user->id, null, [
            'username'  => $user->username,
            'full_name' => $user->full_name,
            'role_id'   => $user->role_id,
        ]);

        return redirect()->route('users.index')->with('success', "Đã tạo tài khoản nhân viên [{$user->username}] thành công!");
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:100',
            'role_id'   => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ]);

        // Không cho phép tự vô hiệu hóa tài khoản của chính mình
        if ($user->id === Auth::id() && !$request->is_active) {
            return redirect()->route('users.index')->with('error', 'Bạn không thể vô hiệu hóa tài khoản của chính mình!');
        }

        $oldValues = $user->only(['full_name', 'role_id', 'is_active']);

        $user->update([
            'full_name' => trim($request->full_name),
            'role_id'   => (int)$request->role_id,
            'is_active' => (bool)$request->is_active,
        ]);

        AuditLog::logAction(Auth::id(), 'UPDATE', 'users', $user->id, $oldValues, $user->only(['full_name', 'role_id', 'is_active']));

        return redirect()->route('users.index')->with('success', "Cập nhật tài khoản [{$user->username}] thành công!");
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        AuditLog::logAction(Auth::id(), 'RESET_PASSWORD', 'users', $user->id, null, [
            'action' => 'Admin đặt lại mật khẩu'
        ]);

        return redirect()->route('users.index')->with('success', "Đặt lại mật khẩu cho tài khoản [{$user->username}] thành công!");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'Bạn không thể tự xóa tài khoản của chính mình!');
        }

        // Kiểm tra xem nhân viên đã từng tạo phiếu nhập/xuất nào chưa
        $hasReceipts = Receipt::where('creator_id', $id)->exists();
        $hasIssues = Issue::where('creator_id', $id)->exists();

        if ($hasReceipts || $hasIssues) {
            // Không xóa cứng để đảm bảo toàn vẹn dữ liệu, thay vào đó khóa tài khoản
            $user->update(['is_active' => false]);
            AuditLog::logAction(Auth::id(), 'DEACTIVATE', 'users', $id, null, ['reason' => 'Khóa thay vì xóa do có dữ liệu liên kết']);
            return redirect()->route('users.index')->with('success', "Tài khoản [{$user->username}] đã có lịch sử chứng từ kho nên được chuyển sang trạng thái [Đã khóa] thay vì xóa hoàn toàn.");
        }

        $old = $user->toArray();
        $user->delete();

        AuditLog::logAction(Auth::id(), 'DELETE', 'users', $id, $old, null);

        return redirect()->route('users.index')->with('success', "Xóa tài khoản nhân viên thành công!");
    }
}
