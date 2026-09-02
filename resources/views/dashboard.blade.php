@extends('layouts.app', ['title' => 'Bảng điều khiển'])

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 50%;
        top: -20px;
        right: -20px;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        border-color: rgba(99, 102, 241, 0.2);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: -1px;
    }

    .stat-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .icon-primary {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }

    .icon-accent {
        background: rgba(6, 182, 212, 0.1);
        color: var(--accent);
    }

    .icon-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .icon-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .icon-danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .grid-dashboard {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 1200px) {
        .grid-dashboard {
            grid-template-columns: 1fr;
        }
    }

    /* Zone Layout Grid */
    .zone-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .zone-card {
        background: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
    }

    .zone-card:hover {
        background: rgba(99, 102, 241, 0.05);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .zone-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .zone-letter {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .zone-name {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .zone-bar-bg {
        height: 6px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 3px;
        overflow: hidden;
    }

    .zone-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent) 0%, var(--primary) 100%);
        border-radius: 3px;
    }

    .zone-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    /* Logs timeline */
    .timeline {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-top: 1rem;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        position: relative;
    }

    .timeline-item::after {
        content: '';
        position: absolute;
        width: 1px;
        background: var(--border-color);
        top: 24px;
        bottom: -20px;
        left: 12px;
    }

    .timeline-item:last-child::after {
        display: none;
    }

    .timeline-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
        z-index: 1;
    }

    .timeline-icon.create {
        color: var(--success);
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.1);
    }

    .timeline-icon.update {
        color: var(--warning);
        border-color: rgba(245, 158, 11, 0.3);
        background: rgba(245, 158, 11, 0.1);
    }

    .timeline-icon.delete {
        color: var(--danger);
        border-color: rgba(239, 68, 68, 0.3);
        background: rgba(239, 68, 68, 0.1);
    }

    .timeline-content {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .timeline-text {
        font-size: 0.88rem;
        font-weight: 500;
    }

    .timeline-time {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .timeline-user {
        font-weight: 600;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value">{{ number_format($totalProducts) }}</span>
            <span class="stat-label">Sản phẩm có sẵn</span>
        </div>
        <div class="stat-icon icon-primary">
            <i class="fa-solid fa-box"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value">{{ number_format($totalCartons) }}</span>
            <span class="stat-label">Tổng số thùng</span>
        </div>
        <div class="stat-icon icon-accent">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value">{{ number_format($totalPieces) }}</span>
            <span class="stat-label">Tổng số cái (PCS)</span>
        </div>
        <div class="stat-icon icon-success">
            <i class="fa-solid fa-calculator"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value {{ $lowStockCount > 0 ? 'text-danger' : '' }}">{{ number_format($lowStockCount) }}</span>
            <span class="stat-label">Cảnh báo dưới định mức</span>
        </div>
        <div class="stat-icon {{ $lowStockCount > 0 ? 'icon-danger' : 'icon-warning' }}">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(15,23,42,0.4));">
        <div class="stat-info">
            <span class="stat-value" style="font-size: 1.5rem; color: var(--accent);">{{ number_format($totalInventoryValue) }}&nbsp;₫</span>
            <span class="stat-label">Giá trị tồn kho hiện tại</span>
        </div>
        <div class="stat-icon icon-accent">
            <i class="fa-solid fa-coins"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value" style="color: var(--success);">{{ number_format($todayInbound) }}</span>
            <span class="stat-label">Phiếu nhập hôm nay</span>
        </div>
        <div class="stat-icon icon-success">
            <i class="fa-solid fa-truck-ramp-box"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-value" style="color: var(--primary);">{{ number_format($todayOutbound) }}</span>
            <span class="stat-label">Phiếu xuất hôm nay</span>
        </div>
        <div class="stat-icon icon-primary">
            <i class="fa-solid fa-truck-moving"></i>
        </div>
    </div>
</div>

<div class="grid-dashboard">
    <!-- Left panel: 7 Zone Layout Map overview -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 600;">Sơ đồ và Trạng thái 7 Dãy Kho</h3>
            <a href="{{ route('zone-map') }}" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Xem chi tiết 7 dãy kho <i class="fa-solid fa-angle-right"></i></a>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Thống kê lượng hàng hóa thực tế đang chứa tại các Dãy từ A đến G.</p>

        <div class="zone-grid">
            @foreach ($zoneStats as $stat)
                @php 
                    $maxCartonsPerZone = 40;
                    $percent = min(100, round(($stat->total_cartons / $maxCartonsPerZone) * 100));
                @endphp
                <a href="{{ route('zone-map', ['zone' => $stat->zone]) }}" class="zone-card">
                    <div class="zone-header">
                        <span class="zone-name">Dãy {{ $stat->zone }}</span>
                        <div class="zone-letter">{{ $stat->zone }}</div>
                    </div>
                    <div style="margin-top: 0.25rem;">
                        <div class="zone-bar-bg">
                            <div class="zone-bar-fill" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    <div class="zone-meta">
                        <span>{{ $stat->total_cartons }} thùng</span>
                        <span>{{ $stat->total_pieces }} cái</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Right panel: Recent Activities (Audit Log) -->
    <div class="card">
        <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 1rem;">Hoạt động gần đây</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Nhật ký xuất nhập và chỉnh sửa dữ liệu kho.</p>

        <div class="timeline">
            @if ($recentLogs->isEmpty())
                <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-size: 0.9rem;">
                    Chưa có hoạt động nào được ghi nhận.
                </div>
            @else
                @foreach ($recentLogs as $log)
                    @php 
                        $iconClass = 'create';
                        $icon = 'fa-plus';
                        if ($log->action == 'UPDATE') { $iconClass = 'update'; $icon = 'fa-pen'; }
                        if ($log->action == 'DELETE') { $iconClass = 'delete'; $icon = 'fa-trash'; }
                        
                        $desc = "";
                        if ($log->table_name == 'receipts') {
                            $desc = "Tạo phiếu nhập kho <strong>" . ($log->new_values['receipt_code'] ?? '') . "</strong>";
                        } elseif ($log->table_name == 'issues') {
                            $desc = "Tạo phiếu xuất kho <strong>" . ($log->new_values['issue_code'] ?? '') . "</strong>";
                        } else {
                            $desc = "Thực hiện " . $log->action . " trên bảng " . $log->table_name;
                        }
                    @endphp
                    <div class="timeline-item">
                        <div class="timeline-icon {{ $iconClass }}"><i class="fa-solid {{ $icon }}"></i></div>
                        <div class="timeline-content">
                            <span class="timeline-text">
                                <span class="timeline-user">{{ $log->user->full_name ?? 'Hệ thống' }}</span> 
                                {!! $desc !!}
                            </span>
                            <span class="timeline-time"><i class="fa-regular fa-clock"></i> {{ date('d/m/Y H:i', strtotime($log->created_at)) }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
