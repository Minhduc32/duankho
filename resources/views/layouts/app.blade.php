<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Hệ thống Quản lý Kho' }}</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --bg-sidebar: #1e293b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #06b6d4;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition);
        }

        .brand-section {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .brand-icon {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-item-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 0.5rem 0.25rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active {
            background-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .user-profile {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: rgba(0, 0, 0, 0.15);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-grow: 1;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .btn-logout {
            color: var(--danger);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .btn-logout:hover {
            transform: scale(1.1);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            min-height: 100vh;
        }

        /* Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Responsive Layout adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }

        /* Cards and Elements shared */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }

        /* Alert notifications */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        /* === Notification Bell === */
        .notif-wrapper {
            position: relative;
        }

        .notif-bell {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: var(--transition);
            position: relative;
        }

        .notif-bell:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger, #ef4444);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .notif-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 380px;
            max-height: 520px;
            background: rgba(15, 23, 42, 0.97);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(20px);
            z-index: 9999;
            overflow: hidden;
            display: none;
            animation: slideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .notif-panel.open {
            display: flex;
            flex-direction: column;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notif-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .notif-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }

        .notif-list {
            overflow-y: auto;
            flex: 1;
        }

        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            text-decoration: none;
            color: var(--text-main);
            transition: background 0.2s;
        }

        .notif-item:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .notif-icon.danger  { background: rgba(239, 68, 68, 0.15);  color: #f87171; }
        .notif-icon.warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .notif-icon.success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .notif-icon.info    { background: rgba(6, 182, 212, 0.15);  color: #22d3ee; }

        .notif-body {
            flex: 1;
            min-width: 0;
        }

        .notif-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.2rem;
        }

        .notif-msg {
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .notif-badge-pill {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 5px;
            margin-top: 0.35rem;
            display: inline-block;
        }

        .notif-badge-pill.danger  { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .notif-badge-pill.warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .notif-badge-pill.success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .notif-badge-pill.info    { background: rgba(6, 182, 212, 0.15); color: #22d3ee; }

        .notif-empty {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .notif-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            flex-shrink: 0;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-section">
            <div class="brand-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <span class="brand-name">IMS Warehouse</span>
        </div>

        <ul class="nav-menu">
            <li class="nav-item-title">Tổng quan</li>
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>
            <li>
                <a href="{{ route('zone-map') }}" class="nav-link {{ Route::is('zone-map') ? 'active' : '' }}">
                    <i class="fa-solid fa-warehouse"></i>
                    <span>Sơ đồ 7 dãy kho</span>
                </a>
            </li>

            <li class="nav-item-title">Giao dịch kho</li>
            <li>
                <a href="{{ route('inbound.index') }}" class="nav-link {{ Route::is('inbound.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-down"></i>
                    <span>Nhập kho (Inbound)</span>
                </a>
            </li>
            <li>
                <a href="{{ route('outbound.index') }}" class="nav-link {{ Route::is('outbound.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-up"></i>
                    <span>Xuất kho (Outbound)</span>
                </a>
            </li>

            <li class="nav-item-title">Dữ liệu & Báo cáo</li>
            <li>
                <a href="{{ route('products.index') }}" class="nav-link {{ Route::is('products.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-box"></i>
                    <span>Danh mục sản phẩm</span>
                </a>
            </li>
            <li>
                <a href="{{ route('reports.index') }}" class="nav-link {{ Route::is('reports.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                    <span>Báo cáo & Thống kê</span>
                </a>
            </li>
            <li>
                <a href="{{ route('audit-logs') }}" class="nav-link {{ Route::is('audit-logs') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Nhật ký Audit Log</span>
                </a>
            </li>

            @auth
                @if (Auth::user()->role && Auth::user()->role->name === 'admin')
                    <li class="nav-item-title">Hệ thống</li>
                    <li>
                        <a href="{{ route('users.index') }}" class="nav-link {{ Route::is('users.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users-gear"></i>
                            <span>Quản lý nhân viên</span>
                        </a>
                    </li>
                @endif
            @endauth
        </ul>

        <!-- User profile footer -->
        @auth
            <div class="user-profile">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->full_name, 0, 1)) }}
                </div>
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()->full_name }}</span>
                    <span class="user-role">{{ Auth::user()->role->name ?? 'User' }}</span>
                </div>
                <button type="button" class="btn-logout" title="Đổi mật khẩu cá nhân" onclick="openModal('myPasswordModal')" style="color: var(--warning); padding: 0.4rem;">
                    <i class="fa-solid fa-key"></i>
                </button>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout" title="Đăng xuất">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top header -->
        <header class="top-header">
            <h2 class="page-title">{{ $title ?? 'Bảng điều khiển' }}</h2>
            <div class="top-actions">
                <span>Hôm nay: <strong>{{ date('d/m/Y') }}</strong></span>

                <!-- Notification Bell -->
                <div class="notif-wrapper" id="notifWrapper">
                    <button class="notif-bell" id="notifBell" onclick="toggleNotifPanel()" title="Thông báo">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                    </button>

                    <div class="notif-panel" id="notifPanel">
                        <div class="notif-header">
                            <h4><i class="fa-solid fa-bell" style="color: var(--accent); margin-right: 0.5rem;"></i>Thông báo hệ thống</h4>
                            <span id="notifCountLabel" style="font-size: 0.8rem; color: var(--text-muted);">...</span>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">
                                <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                                Đang tải thông báo...
                            </div>
                        </div>
                        <div class="notif-footer">
                            Tự động cập nhật mỗi 30 giây &middot; <span id="notifLastUpdate">--:--</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Active View -->
        @yield('content')
    </main>

    @stack('scripts')

<style>
/* ===== Toast Notification ===== */
#toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    pointer-events: none;
}

.toast-item {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    min-width: 300px;
    max-width: 380px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    backdrop-filter: blur(20px);
    pointer-events: all;
    animation: toastIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    position: relative;
    overflow: hidden;
}

.toast-item.hiding {
    animation: toastOut 0.3s ease forwards;
}

@keyframes toastIn {
    from { opacity: 0; transform: translateX(40px) scale(0.95); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

@keyframes toastOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(40px); }
}

.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    border-radius: 0 0 0 14px;
    animation: toastProgress 5s linear forwards;
}

@keyframes toastProgress {
    from { width: 100%; }
    to   { width: 0%; }
}

.toast-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.toast-icon.success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.toast-icon.primary { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
.toast-icon.info    { background: rgba(6, 182, 212, 0.15);  color: #22d3ee; }
.toast-icon.warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.toast-icon.danger  { background: rgba(239, 68, 68, 0.15);  color: #f87171; }

.toast-progress.success { background: #34d399; }
.toast-progress.primary { background: #818cf8; }
.toast-progress.info    { background: #22d3ee; }
.toast-progress.warning { background: #fbbf24; }
.toast-progress.danger  { background: #f87171; }

.toast-body { flex: 1; min-width: 0; }

.toast-title {
    font-size: 0.87rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.2rem;
}

.toast-message {
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.45;
}

.toast-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 1px;
    transition: color 0.2s;
}
.toast-close:hover { color: white; }
</style>

<!-- Toast Container -->
<div id="toast-container"></div>

@if (session('toast'))
<script>
window.__initToast = @json(session('toast'));
</script>
@endif

@if (session('error'))
@php $errorToast = ['type'=>'danger','icon'=>'fa-circle-xmark','title'=>'Có lỗi xảy ra','message'=>session('error')]; @endphp
<script>
window.__initToastError = @json($errorToast);
</script>
@endif

<script>
const NOTIF_API_URL    = '{{ route("notifications") }}';
const NOTIF_RECENT_URL = '{{ route("notifications.recent") }}';

// ========= Real-time polling engine =========
// Lưu thời điểm cuối cùng đã kiểm tra để chỉ lấy phiếu MỚI
let realtimeLastCheck = new Date().toISOString();
// Lưu ID các sự kiện đã show toast để tránh trùng lặp
const seenEventIds = new Set();

function startRealtimePolling() {
    setInterval(() => {
        fetch(NOTIF_RECENT_URL + '?since=' + encodeURIComponent(realtimeLastCheck))
            .then(res => res.json())
            .then(data => {
                // Cập nhật thời điểm kiểm tra lần kế tiếp
                if (data.server_now) realtimeLastCheck = data.server_now;

                if (!data.has_new || !data.events) return;

                data.events.forEach(evt => {
                    if (seenEventIds.has(evt.id)) return; // Đã show rồi
                    seenEventIds.add(evt.id);

                    // Show toast ngay lập tức
                    showToast(evt.type, evt.icon, evt.title, evt.message);

                    // Rung badge (highlight animation)
                    const badge = document.getElementById('notifBadge');
                    badge.style.animation = 'none';
                    badge.offsetHeight; // trigger reflow
                    badge.style.animation = '';
                });

                // Cập nhật badge nếu có sự kiện mới
                if (data.has_new) {
                    refreshBadge();
                    const panel = document.getElementById('notifPanel');
                    if (panel && panel.classList.contains('open')) {
                        loadNotifications();
                    }
                }
            })
            .catch(() => {}); // Im lặng khi lỗi mạng
    }, 10000); // Poll mỗi 10 giây
}

function refreshBadge() {
    fetch(NOTIF_API_URL)
        .then(res => res.json())
        .then(data => {
            const count = data.count || 0;
            const badge = document.getElementById('notifBadge');
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        })
        .catch(() => {});
}


// ========= Toast System =========
function showToast(type, icon, title, message) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast-item';

    toast.innerHTML = `
        <div class="toast-icon ${type}">
            <i class="fa-solid ${icon}"></i>
        </div>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this.parentElement)">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="toast-progress ${type}"></div>
    `;

    container.appendChild(toast);

    setTimeout(() => dismissToast(toast), 5200);
}

function dismissToast(el) {
    if (!el || el.classList.contains('hiding')) return;
    el.classList.add('hiding');
    el.addEventListener('animationend', () => el.remove(), { once: true });
}

// Show toast from server session
if (window.__initToast) {
    const t = window.__initToast;
    setTimeout(() => showToast(t.type, t.icon, t.title, t.message), 200);
}
if (window.__initToastError) {
    const t = window.__initToastError;
    setTimeout(() => showToast(t.type, t.icon, t.title, t.message), 200);
}

// ========= Notification Bell =========
function toggleNotifPanel() {
    const panel = document.getElementById('notifPanel');
    const isOpen = panel.classList.contains('open');
    if (isOpen) {
        panel.classList.remove('open');
    } else {
        panel.classList.add('open');
        loadNotifications();
    }
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notifWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notifPanel').classList.remove('open');
    }
});

function loadNotifications() {
    fetch(NOTIF_API_URL)
        .then(res => res.json())
        .then(data => { renderNotifications(data); })
        .catch(() => {
            document.getElementById('notifList').innerHTML = `
                <div class="notif-empty">
                    <i class="fa-solid fa-circle-xmark" style="color:#ef4444;font-size:1.5rem;margin-bottom:0.5rem;display:block;"></i>
                    Không thể tải thông báo.
                </div>`;
        });
}

function renderNotifications(data) {
    const badge = document.getElementById('notifBadge');
    const list  = document.getElementById('notifList');
    const label = document.getElementById('notifCountLabel');
    const lastUpdate = document.getElementById('notifLastUpdate');

    const count = data.count || 0;
    badge.textContent = count > 99 ? '99+' : count;
    badge.style.display = count > 0 ? 'flex' : 'none';
    label.textContent = count + ' thông báo';

    const now = new Date();
    lastUpdate.textContent = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');

    if (!data.notifications || data.notifications.length === 0) {
        list.innerHTML = `<div class="notif-empty">
            <i class="fa-solid fa-check-circle" style="color:#10b981;font-size:1.5rem;margin-bottom:0.5rem;display:block;"></i>
            Không có thông báo nào.<br><small>Hệ thống đang hoạt động bình thường.</small>
        </div>`;
        return;
    }

    let html = '';
    data.notifications.forEach(n => {
        html += `<a href="${n.link}" class="notif-item">
            <div class="notif-icon ${n.type}"><i class="fa-solid ${n.icon}"></i></div>
            <div class="notif-body">
                <div class="notif-title">${n.title}</div>
                <div class="notif-msg">${n.message}</div>
                <span class="notif-badge-pill ${n.type}">${n.badge}</span>
            </div>
        </a>`;
    });
    list.innerHTML = html;
}

// Init badge on page load
fetch(NOTIF_API_URL)
    .then(res => res.json())
    .then(data => {
        const count = data.count || 0;
        const badge = document.getElementById('notifBadge');
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    })
    .catch(() => {});

// Auto-refresh badge every 30s (thống kê tổng)
setInterval(() => {
    const panel = document.getElementById('notifPanel');
    if (panel && panel.classList.contains('open')) {
        loadNotifications();
    } else {
        refreshBadge();
    }
}, 30000);

// 🚀 Khởi động real-time polling (mỗi 10 giây phát hiện phiếu mới)
startRealtimePolling();

function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'flex';
        el.classList.add('active');
    }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'none';
        el.classList.remove('active');
    }
}
</script>

@auth
<!-- Modal Đổi Mật Khẩu Cá Nhân -->
<div class="modal" id="myPasswordModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background:rgba(15,23,42,0.75); backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:var(--bg-sidebar); border:1px solid var(--border-color); border-radius:20px; width:100%; max-width:440px; padding:2rem; box-shadow:0 20px 40px rgba(0,0,0,0.4);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="font-size:1.2rem; font-weight:700; color:white;"><i class="fa-solid fa-key" style="color:var(--warning); margin-right:0.5rem;"></i>Đổi mật khẩu cá nhân</h3>
            <button onclick="closeModal('myPasswordModal')" style="background:none; border:none; color:var(--text-muted); font-size:1.3rem; cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-muted); margin-bottom:0.4rem; text-transform:uppercase;">Mật khẩu hiện tại *</label>
                <input type="password" name="current_password" required class="form-control" style="width:100%; padding:0.7rem 1rem; background:rgba(15,23,42,0.5); border:1px solid var(--border-color); border-radius:10px; color:white;" placeholder="••••••">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-muted); margin-bottom:0.4rem; text-transform:uppercase;">Mật khẩu mới * (tối thiểu 6 ký tự)</label>
                <input type="password" name="new_password" required minlength="6" class="form-control" style="width:100%; padding:0.7rem 1rem; background:rgba(15,23,42,0.5); border:1px solid var(--border-color); border-radius:10px; color:white;" placeholder="••••••">
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text-muted); margin-bottom:0.4rem; text-transform:uppercase;">Nhập lại mật khẩu mới *</label>
                <input type="password" name="new_password_confirmation" required minlength="6" class="form-control" style="width:100%; padding:0.7rem 1rem; background:rgba(15,23,42,0.5); border:1px solid var(--border-color); border-radius:10px; color:white;" placeholder="••••••">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('myPasswordModal')">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Lưu mật khẩu</button>
            </div>
        </form>
    </div>
</div>
@endauth
@stack('scripts')
</body>
</html>

