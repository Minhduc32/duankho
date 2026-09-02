@extends('layouts.app', ['title' => 'Quản lý nhân viên & Phân quyền'])

@push('styles')
<style>
    .table-container {
        overflow-x: auto;
        margin-top: 1rem;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.95rem;
    }

    .table th, .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .table tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-role-admin { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-role-manager { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
    .badge-role-accountant { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .badge-role-staff { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

    .badge-active { background: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-inactive { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        margin-right: 0.75rem;
        vertical-align: middle;
    }

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: var(--bg-sidebar);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        width: 100%;
        max-width: 520px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        position: relative;
        animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }

    .btn-close {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 1.25rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-close:hover {
        color: white;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: white;
        font-size: 0.95rem;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .filter-bar {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Danh sách nhân viên & Người dùng</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Quản lý tài khoản, phân quyền vai trò và trạng thái hoạt động trong hệ thống.</p>
        </div>
        
        <button class="btn btn-primary" onclick="openModal('addModal')">
            <i class="fa-solid fa-user-plus"></i> Thêm nhân viên
        </button>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm theo tên đăng nhập hoặc họ tên...">
        </div>
        <div style="min-width: 180px;">
            <select name="role_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tất cả vai trò --</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                        {{ strtoupper($r->name) }} ({{ $r->description }})
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fa-solid fa-filter"></i> Lọc
        </button>
        @if (request()->hasAny(['q', 'role_id']))
            <a href="{{ route('users.index') }}" class="btn btn-secondary" style="color: var(--text-muted);">
                <i class="fa-solid fa-xmark"></i> Xóa lọc
            </a>
        @endif
    </form>

    <!-- Users Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nhân viên</th>
                    <th>Tên đăng nhập</th>
                    <th>Vai trò (Role)</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $index => $u)
                    @php
                        $roleClass = match($u->role->name ?? '') {
                            'admin' => 'badge-role-admin',
                            'manager' => 'badge-role-manager',
                            'accountant' => 'badge-role-accountant',
                            default => 'badge-role-staff',
                        };
                    @endphp
                    <tr>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $index + 1 }}</td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <div class="avatar-circle">
                                    {{ strtoupper(substr($u->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: white;">
                                        {{ $u->full_name }}
                                        @if ($u->id === Auth::id())
                                            <span style="font-size: 0.7rem; background: rgba(99, 102, 241, 0.2); color: #a5b4fc; padding: 2px 6px; border-radius: 4px; margin-left: 0.25rem;">Bạn</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #{{ $u->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-family: monospace; font-weight: 600; color: var(--accent);">{{ $u->username }}</td>
                        <td>
                            <span class="badge {{ $roleClass }}">
                                {{ strtoupper($u->role->name ?? 'N/A') }}
                            </span>
                        </td>
                        <td>
                            @if ($u->is_active)
                                <span class="badge badge-active"><i class="fa-solid fa-check"></i> Đang hoạt động</span>
                            @else
                                <span class="badge badge-inactive"><i class="fa-solid fa-lock"></i> Đã khóa</span>
                            @endif
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" 
                                        onclick="openEditModal({{ json_encode($u) }})" title="Chỉnh sửa thông tin">
                                    <i class="fa-solid fa-pen"></i> Sửa
                                </button>
                                
                                <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; color: var(--warning);" 
                                        onclick="openResetModal({{ json_encode($u) }})" title="Đặt lại mật khẩu">
                                    <i class="fa-solid fa-key"></i> Đổi MK
                                </button>

                                @if ($u->id !== Auth::id())
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa hoặc khóa tài khoản [{{ $u->username }}]?')">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; color: var(--danger);" title="Xóa/Khóa tài khoản">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                            Không tìm thấy nhân viên nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Nhân Viên Mới -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-user-plus" style="color: var(--primary); margin-right: 0.5rem;"></i>Thêm nhân viên mới</h3>
            <button class="btn-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tên đăng nhập (Username) *</label>
                <input type="text" name="username" class="form-control" placeholder="vd: ducnv, hoangvt..." required>
            </div>
            <div class="form-group">
                <label class="form-label">Họ và tên đầy đủ *</label>
                <input type="text" name="full_name" class="form-control" placeholder="vd: Nguyễn Văn Đức" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mật khẩu khởi tạo * (ít nhất 6 ký tự)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••" required minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Vai trò & Phân quyền *</label>
                <select name="role_id" class="form-control" required>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}">
                            {{ strtoupper($r->name) }} - {{ $r->description }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Trạng thái tài khoản</label>
                <select name="is_active" class="form-control">
                    <option value="1">Kích hoạt ngay (Đang hoạt động)</option>
                    <option value="0">Tạm khóa</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Lưu nhân viên</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Nhân Viên -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-user-pen" style="color: var(--warning); margin-right: 0.5rem;"></i>Cập nhật thông tin nhân viên</h3>
            <button class="btn-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" id="edit_username" class="form-control" readonly style="opacity: 0.6; cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label class="form-label">Họ và tên đầy đủ *</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Vai trò & Phân quyền *</label>
                <select name="role_id" id="edit_role_id" class="form-control" required>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}">
                            {{ strtoupper($r->name) }} - {{ $r->description }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Trạng thái tài khoản *</label>
                <select name="is_active" id="edit_is_active" class="form-control" required>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Khóa tài khoản</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Mật Khẩu -->
<div class="modal" id="resetModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-key" style="color: var(--warning); margin-right: 0.5rem;"></i>Đặt lại mật khẩu</h3>
            <button class="btn-close" onclick="closeModal('resetModal')">&times;</button>
        </div>
        <form id="resetForm" method="POST">
            @csrf
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                Đặt lại mật khẩu mới cho nhân viên <strong id="reset_user_label" style="color: white;"></strong>:
            </p>
            <div class="form-group">
                <label class="form-label">Mật khẩu mới * (ít nhất 6 ký tự)</label>
                <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••">
            </div>
            <div class="form-group">
                <label class="form-label">Xác nhận mật khẩu mới *</label>
                <input type="password" name="new_password_confirmation" class="form-control" required minlength="6" placeholder="••••••">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('resetModal')">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Xác nhận đổi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openEditModal(user) {
    document.getElementById('editForm').action = `/users/${user.id}`;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role_id').value = user.role_id;
    document.getElementById('edit_is_active').value = user.is_active ? '1' : '0';
    openModal('editModal');
}

function openResetModal(user) {
    document.getElementById('resetForm').action = `/users/${user.id}/reset-password`;
    document.getElementById('reset_user_label').textContent = `${user.full_name} (${user.username})`;
    openModal('resetModal');
}

// Đóng modal khi click ra ngoài vùng content
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(modal => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>
@endpush
