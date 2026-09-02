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

<!-- Quick Barcode / Carton Lookup Card -->
<div class="card" style="margin-top: 1.5rem; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(30,41,59,0.7) 0%, rgba(15,23,42,0.8) 100%);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: white;">
                <i class="fa-solid fa-barcode" style="color: var(--accent); margin-right: 0.5rem;"></i>Tra cứu nhanh Thùng hàng / Barcode
            </h3>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem;">
                Quét mã barcode hoặc nhập mã thùng (vd: C-LOT...), mã SKU, mã vị trí để tra cứu vị trí ô kệ và số lượng tức thì.
            </p>
        </div>
    </div>
    
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
            <input type="text" id="cartonLookupInput" class="form-control" style="width: 100%; padding: 0.75rem 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 10px; color: white;" placeholder="Nhập mã thùng hàng, SKU hoặc mã vạch vị trí..." onkeyup="if(event.key==='Enter') executeCartonLookup()">
        </div>
        <button type="button" class="btn btn-primary" onclick="executeCartonLookup()">
            <i class="fa-solid fa-magnifying-glass"></i> Tra cứu
        </button>
    </div>

    <div id="cartonLookupResult" style="display: none; margin-top: 1.25rem;">
        <div id="cartonLookupSpinner" style="display:none; text-align:center; padding: 1.5rem; color: var(--accent);">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 0.5rem; font-size: 0.85rem;">Đang tìm kiếm dữ liệu thùng hàng...</p>
        </div>
        <div id="cartonLookupContent"></div>
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

@push('scripts')
<script>
function executeCartonLookup() {
    const input = document.getElementById('cartonLookupInput');
    const code = input.value.trim();
    if (!code) {
        alert('Vui lòng nhập mã để tra cứu.');
        return;
    }

    const container = document.getElementById('cartonLookupResult');
    const spinner = document.getElementById('cartonLookupSpinner');
    const content = document.getElementById('cartonLookupContent');

    container.style.display = 'block';
    spinner.style.display = 'block';
    content.innerHTML = '';

    fetch(`/api/carton/lookup?code=${encodeURIComponent(code)}`)
        .then(res => res.json())
        .then(data => {
            spinner.style.display = 'none';
            if (data.error) {
                content.innerHTML = `<div class="alert alert-error" style="padding:0.75rem 1rem; border-radius:10px; background:rgba(239,68,68,0.1); color:#f87171; border:1px solid rgba(239,68,68,0.2);">${data.error}</div>`;
                return;
            }
            if (!data.cartons || data.cartons.length === 0) {
                content.innerHTML = `<div style="text-align:center; padding:1.5rem; color:var(--text-muted); background:rgba(255,255,255,0.02); border-radius:12px;">Không tìm thấy thùng hàng nào khớp với mã <strong>"${code}"</strong>.</div>`;
                return;
            }

            let html = `<div style="overflow-x:auto;"><table class="table" style="width:100%; border-collapse:collapse; font-size:0.88rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border-color); color:var(--text-muted); text-transform:uppercase; font-size:0.75rem;">
                        <th style="padding:0.6rem; text-align:left;">Mã thùng</th>
                        <th style="padding:0.6rem; text-align:left;">Sản phẩm</th>
                        <th style="padding:0.6rem; text-align:left;">Số lượng (Tồn/Gốc)</th>
                        <th style="padding:0.6rem; text-align:left;">Vị trí lưu kho</th>
                        <th style="padding:0.6rem; text-align:left;">Mã vạch vị trí</th>
                        <th style="padding:0.6rem; text-align:left;">Ngày nhập</th>
                        <th style="padding:0.6rem; text-align:right;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>`;

            data.cartons.forEach(c => {
                const isStock = c.status === 'IN_STOCK';
                const badgeColor = isStock ? 'background:rgba(16,185,129,0.15); color:#34d399;' : 'background:rgba(245,158,11,0.15); color:#fbbf24;';
                html += `<tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:0.6rem; font-family:monospace; font-weight:700; color:white;">${c.carton_code}</td>
                    <td style="padding:0.6rem;"><strong>${c.product_name}</strong> <span style="color:var(--text-muted); font-size:0.8rem;">(${c.sku})</span></td>
                    <td style="padding:0.6rem;"><span style="color:var(--accent); font-weight:700;">${c.current_pieces}</span> / ${c.original_pieces} cái</td>
                    <td style="padding:0.6rem;"><i class="fa-solid fa-location-dot" style="color:var(--primary); margin-right:0.3rem;"></i>${c.location_label}</td>
                    <td style="padding:0.6rem; font-family:monospace;">${c.location_barcode}</td>
                    <td style="padding:0.6rem; color:var(--text-muted); font-size:0.8rem;">${c.received_at}</td>
                    <td style="padding:0.6rem; text-align:right;">
                        <span style="display:inline-block; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:600; ${badgeColor}">
                            ${isStock ? 'Trong kho' : 'Đã xuất'}
                        </span>
                    </td>
                </tr>`;
            });

            html += `</tbody></table></div>`;
            content.innerHTML = html;
        })
        .catch(err => {
            spinner.style.display = 'none';
            content.innerHTML = `<div class="alert alert-error" style="padding:0.75rem 1rem; border-radius:10px; background:rgba(239,68,68,0.1); color:#f87171; border:1px solid rgba(239,68,68,0.2);">Lỗi khi tra cứu: ${err.message}</div>`;
        });
}
</script>
@endpush
