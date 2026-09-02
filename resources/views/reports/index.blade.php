@extends('layouts.app', ['title' => 'Trung tâm Báo cáo & Thống kê'])

@push('styles')
<style>
    /* Tab System Styling */
    .tab-container {
        display: flex;
        gap: 0.5rem;
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        backdrop-filter: blur(10px);
    }

    .tab-btn {
        flex: 1;
        min-width: 140px;
        padding: 0.8rem 1rem;
        text-align: center;
        background: none;
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .tab-btn:hover:not(.active) {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }

    /* Filter Bar */
    .filter-bar {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 2rem;
        backdrop-filter: blur(10px);
    }

    .filter-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }

    .filter-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        flex-wrap: wrap;
        margin-top: 1.25rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1.25rem;
    }

    /* Table styling */
    .report-table th {
        text-align: center;
        vertical-align: middle;
        border: 1px solid var(--border-color) !important;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 0.75rem;
    }

    .report-table td {
        vertical-align: middle;
        border: 1px solid var(--border-color);
        padding: 0.75rem;
        font-size: 0.9rem;
    }

    .report-table td.num-cell {
        text-align: right;
        font-family: monospace;
        font-weight: 500;
    }

    .report-header-group {
        background: rgba(255, 255, 255, 0.03);
    }

    /* Summary Stats Grid */
    .stats-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-summary-card {
        background: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border-color);
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
    }

    .stat-summary-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        margin-top: 0.25rem;
    }

    .stat-summary-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
    }

    .badge-status {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        display: inline-block;
    }
    
    .badge-create { background: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-update { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
    .badge-delete { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

    .badge-normal { background: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-low { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
    .badge-over { background: rgba(245, 158, 11, 0.15); color: var(--warning); }

    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        .sidebar, .filter-bar, .tab-container, .top-header, .btn, .filter-actions {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .card {
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            backdrop-filter: none !important;
        }
        .report-table th, .report-table td {
            color: black !important;
            border-color: #333 !important;
        }
        .report-table tr:hover {
            background: none !important;
        }
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 2rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Tabs Selector -->
<div class="tab-container">
    <a href="{{ route('reports.index', ['tab' => 'nxt', 'date' => $date]) }}" class="tab-btn {{ $tab == 'nxt' ? 'active' : '' }}">
        <i class="fa-solid fa-chart-pie"></i>
        <span>NXT Ngày</span>
    </a>
    <a href="{{ route('reports.index', ['tab' => 'inbound', 'from_date' => $from_date, 'to_date' => $to_date]) }}" class="tab-btn {{ $tab == 'inbound' ? 'active' : '' }}">
        <i class="fa-solid fa-circle-down"></i>
        <span>Nhập Kho</span>
    </a>
    <a href="{{ route('reports.index', ['tab' => 'outbound', 'from_date' => $from_date, 'to_date' => $to_date]) }}" class="tab-btn {{ $tab == 'outbound' ? 'active' : '' }}">
        <i class="fa-solid fa-circle-up"></i>
        <span>Xuất Kho</span>
    </a>
    <a href="{{ route('reports.index', ['tab' => 'inventory']) }}" class="tab-btn {{ $tab == 'inventory' ? 'active' : '' }}">
        <i class="fa-solid fa-boxes-stacked"></i>
        <span>Tồn Kho</span>
    </a>
    <a href="{{ route('reports.index', ['tab' => 'occupancy']) }}" class="tab-btn {{ $tab == 'occupancy' ? 'active' : '' }}">
        <i class="fa-solid fa-warehouse"></i>
        <span>Hiệu Suất Kho</span>
    </a>
    <a href="{{ route('reports.index', ['tab' => 'audit', 'from_date' => $from_date, 'to_date' => $to_date]) }}" class="tab-btn {{ $tab == 'audit' ? 'active' : '' }}">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>Audit Logs</span>
    </a>
</div>

<!-- Header on print only -->
<div class="print-header" style="display: none;">
    <h1 style="font-size: 1.8rem; font-weight: 700;">HỆ THỐNG KHO IMS WAREHOUSE</h1>
    <p style="font-size: 1rem; margin-top: 0.5rem;">Báo cáo xuất ngày: {{ date('d/m/Y H:i') }}</p>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form action="{{ route('reports.index') }}" method="GET" id="filterForm">
        <input type="hidden" name="tab" value="{{ $tab }}">
        
        <div class="filter-form-grid">
            @if ($tab == 'nxt')
                <!-- NXT Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="date" class="form-label">Chọn ngày báo cáo</label>
                    <input type="date" id="date" name="date" class="form-control" value="{{ $date }}">
                </div>
            @endif

            @if (in_array($tab, ['inbound', 'outbound', 'audit']))
                <!-- Date Range Filters -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="from_date" class="form-label">Từ ngày</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="{{ $from_date }}">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="to_date" class="form-label">Đến ngày</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="{{ $to_date }}">
                </div>
            @endif

            @if (in_array($tab, ['inbound', 'outbound', 'inventory']))
                <!-- Product Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="product_id" class="form-label">Sản phẩm</label>
                    <select id="product_id" name="product_id" class="form-control">
                        <option value="">-- Tất cả sản phẩm --</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ $product_id == $p->id ? 'selected' : '' }}>
                                [{{ $p->sku }}] {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($tab == 'inbound')
                <!-- PO Number Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="po_number" class="form-label">Số PO</label>
                    <input type="text" id="po_number" name="po_number" class="form-control" placeholder="Mã PO..." value="{{ $po_number }}">
                </div>
            @endif

            @if ($tab == 'outbound')
                <!-- Order Number Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="order_number" class="form-label">Mã Đơn Hàng</label>
                    <input type="text" id="order_number" name="order_number" class="form-control" placeholder="Mã đơn hàng..." value="{{ $order_number }}">
                </div>
            @endif

            @if ($tab == 'inventory')
                <!-- Category Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">Danh mục</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">-- Tất cả danh mục --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($tab, ['inventory', 'occupancy']))
                <!-- Zone Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="zone" class="form-label">Dãy kho</label>
                    <select id="zone" name="zone" class="form-control">
                        <option value="">-- Tất cả các dãy --</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z }}" {{ $zone == $z ? 'selected' : '' }}>
                                Dãy {{ $z }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($tab == 'audit')
                <!-- User Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="user_id" class="form-label">Nhân viên thực hiện</label>
                    <select id="user_id" name="user_id" class="form-control">
                        <option value="">-- Tất cả nhân viên --</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ $user_id == $u->id ? 'selected' : '' }}>
                                {{ $u->full_name }} ({{ $u->username }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Action Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="action_filter" class="form-label">Hành động</label>
                    <select id="action_filter" name="action_filter" class="form-control">
                        <option value="">-- Tất cả hành động --</option>
                        @foreach ($actions as $act)
                            <option value="{{ $act }}" {{ $action_filter == $act ? 'selected' : '' }}>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Lọc báo cáo
            </button>
            
            @if ($tab == 'nxt')
                <a href="{{ route('reports.export', ['date' => $date]) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @elseif ($tab == 'inbound')
                <a href="{{ route('reports.export.inbound', compact('from_date', 'to_date', 'product_id', 'po_number')) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @elseif ($tab == 'outbound')
                <a href="{{ route('reports.export.outbound', compact('from_date', 'to_date', 'product_id', 'order_number')) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @elseif ($tab == 'inventory')
                <a href="{{ route('reports.export.inventory', compact('product_id', 'category', 'zone')) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @elseif ($tab == 'occupancy')
                <a href="{{ route('reports.export.occupancy', compact('zone')) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @elseif ($tab == 'audit')
                <a href="{{ route('reports.export.audit', compact('from_date', 'to_date', 'user_id', 'action_filter')) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel (.xlsx)
                </a>
            @endif

            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> In / Xuất PDF
            </button>
        </div>
    </form>
</div>

<!-- Report Content Card -->
<div class="card">
    <!-- Header inside card (visible on screen & print) -->
    <div style="text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
        @if ($tab == 'nxt')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO TỔNG HỢP NHẬP - XUẤT - TỒN (NXT)</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Ngày thực hiện: <strong>{{ date('d/m/Y', strtotime($date)) }}</strong></p>
        @elseif ($tab == 'inbound')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO CHI TIẾT NHẬP KHO (INBOUND)</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Từ ngày: <strong>{{ date('d/m/Y', strtotime($from_date)) }}</strong> - Đến ngày: <strong>{{ date('d/m/Y', strtotime($to_date)) }}</strong></p>
        @elseif ($tab == 'outbound')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO CHI TIẾT XUẤT KHO (OUTBOUND)</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Từ ngày: <strong>{{ date('d/m/Y', strtotime($from_date)) }}</strong> - Đến ngày: <strong>{{ date('d/m/Y', strtotime($to_date)) }}</strong></p>
        @elseif ($tab == 'inventory')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO DANH SÁCH HÀNG TỒN KHO & ĐỊNH MỨC</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Trạng thái kho tại thời điểm xuất: <strong>{{ date('d/m/Y H:i') }}</strong></p>
        @elseif ($tab == 'occupancy')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO HIỆU SUẤT LẤP ĐẦY & SƠ ĐỒ KỆ</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Không gian lưu trữ hiện hành - Ngày: <strong>{{ date('d/m/Y') }}</strong></p>
        @elseif ($tab == 'audit')
            <h2 style="font-size: 1.5rem; font-weight: 700; color: white;">BÁO CÁO NHẬT KÝ HOẠT ĐỘNG HỆ THỐNG</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Khoảng thời gian: <strong>{{ date('d/m/Y', strtotime($from_date)) }}</strong> - <strong>{{ date('d/m/Y', strtotime($to_date)) }}</strong></p>
        @endif
    </div>

    <!-- Summary Stats section if applicable -->
    @if ($tab == 'inventory')
        @php
            $totalOriginal = $reportData->sum('original_pieces');
            $totalCurrent = $reportData->sum('current_pieces');
            $totalCartons = $reportData->count();
            $totalVal = $reportData->sum(function($r) { return $r->current_pieces * $r->price; });
        @endphp
        <div class="stats-summary-grid">
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tổng số thùng tồn</span>
                <div class="stat-summary-val">{{ number_format($totalCartons) }} thùng</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tổng số cái tồn</span>
                <div class="stat-summary-val" style="color: var(--accent);">{{ number_format($totalCurrent) }} PCS</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tỷ lệ hao hụt thùng</span>
                <div class="stat-summary-val" style="color: var(--warning);">
                    {{ $totalOriginal > 0 ? round((1 - ($totalCurrent / $totalOriginal)) * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Ước tính giá trị tồn</span>
                <div class="stat-summary-val" style="color: var(--success);">{{ number_format($totalVal) }}đ</div>
            </div>
        </div>
    @elseif ($tab == 'occupancy')
        @php
            $totalCapacity = $zone ? 20 : 140; // 20 per zone, 140 total
            $occupiedCount = $reportData->filter(function($r) { return $r->total_cartons > 0; })->count();
            $emptyCount = $totalCapacity - $occupiedCount;
            $rate = $totalCapacity > 0 ? round(($occupiedCount / $totalCapacity) * 100, 1) : 0;
            
            $totalCartons = $reportData->sum('total_cartons');
            $totalPieces = $reportData->sum('total_pieces');
        @endphp
        <div class="stats-summary-grid">
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tổng số vị trí</span>
                <div class="stat-summary-val">{{ $totalCapacity }} vị trí</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label font-success" style="color: var(--success);">Vị trí có hàng</span>
                <div class="stat-summary-val" style="color: var(--success);">{{ $occupiedCount }} vị trí</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Vị trí còn trống</span>
                <div class="stat-summary-val" style="color: var(--text-muted);">{{ $emptyCount }} vị trí</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tỷ lệ lấp đầy</span>
                <div class="stat-summary-val" style="color: var(--accent);">{{ $rate }}%</div>
            </div>
            <div class="stat-summary-card">
                <span class="stat-summary-label">Tổng hàng lưu trữ</span>
                <div class="stat-summary-val" style="font-size: 1.1rem; padding-top: 0.2rem;">
                    {{ number_format($totalCartons) }} thùng / {{ number_format($totalPieces) }} cái
                </div>
            </div>
        </div>
    @endif

    <!-- Data Tables -->
    <div style="overflow-x: auto;">
        @if ($tab == 'nxt')
            <!-- 1. NXT Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th rowspan="2">Mã SKU</th>
                        <th rowspan="2" style="text-align: left;">Tên sản phẩm</th>
                        <th rowspan="2">Danh mục</th>
                        <th colspan="2">Tồn đầu kỳ</th>
                        <th colspan="2">Nhập trong ngày</th>
                        <th colspan="2">Xuất trong ngày</th>
                        <th colspan="2">Tồn cuối kỳ</th>
                    </tr>
                    <tr class="report-header-group" style="font-size: 0.75rem;">
                        <th>Thùng</th>
                        <th>Cái (PCS)</th>
                        <th>Thùng</th>
                        <th>Cái (PCS)</th>
                        <th>Thùng</th>
                        <th>Cái (PCS)</th>
                        <th>Thùng</th>
                        <th>Cái (PCS)</th>
                    </tr>
                </thead>
                <tbody>
                    @if (empty($reportData))
                        <tr>
                            <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                Không có phát sinh giao dịch trong ngày này.
                            </td>
                        </tr>
                    @else
                        @foreach ($reportData as $row)
                            <tr>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row['sku'] }}</td>
                                <td style="font-weight: 500;">{{ $row['product_name'] }}</td>
                                <td style="text-align: center; color: var(--text-muted);">{{ $row['category'] ?: '-' }}</td>
                                <td class="num-cell">{{ number_format($row['opening_cartons']) }}</td>
                                <td class="num-cell" style="color: #cbd5e1;">{{ number_format($row['opening_pieces']) }}</td>
                                <td class="num-cell" style="color: var(--success);">{{ number_format($row['in_cartons']) }}</td>
                                <td class="num-cell" style="color: var(--success); font-weight: 600;">{{ number_format($row['in_pieces']) }}</td>
                                <td class="num-cell" style="color: var(--warning);">{{ number_format($row['out_cartons']) }}</td>
                                <td class="num-cell" style="color: var(--warning); font-weight: 600;">{{ number_format($row['out_pieces']) }}</td>
                                <td class="num-cell" style="color: var(--accent);">{{ number_format($row['closing_cartons']) }}</td>
                                <td class="num-cell" style="color: var(--accent); font-weight: 700;">{{ number_format($row['closing_pieces']) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

        @elseif ($tab == 'inbound')
            <!-- 2. Inbound Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th>Mã phiếu</th>
                        <th>Số PO</th>
                        <th>Ngày nhập</th>
                        <th>Người lập</th>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Số Lô</th>
                        <th>Mã Thùng</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Vị trí xếp</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($reportData->isEmpty())
                        <tr>
                            <td colspan="13" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                Không tìm thấy lô hàng nhập kho nào khớp với điều kiện lọc.
                            </td>
                        </tr>
                    @else
                        @foreach ($reportData as $row)
                            <tr>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row->receipt_code }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $row->po_number ?: '-' }}</td>
                                <td style="text-align: center; white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($row->receipt_date)) }}</td>
                                <td>{{ $row->creator_name }}</td>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row->sku }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td style="color: var(--text-muted); text-align: center;">{{ $row->category ?: '-' }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $row->lot_number }}</td>
                                <td style="font-family: monospace; text-align: center; color: var(--accent);">{{ $row->carton_code }}</td>
                                <td class="num-cell">{{ number_format($row->price) }}đ</td>
                                <td class="num-cell" style="color: var(--success); font-weight: 600;">{{ number_format($row->original_pieces) }}</td>
                                <td class="num-cell" style="font-weight: 700;">{{ number_format($row->original_pieces * $row->price) }}đ</td>
                                <td style="text-align: center; white-space: nowrap; font-weight: 500; color: var(--accent);">
                                    Dãy {{ $row->zone }} - {{ $row->rack }} - {{ $row->level }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

        @elseif ($tab == 'outbound')
            <!-- 3. Outbound Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th>Mã phiếu</th>
                        <th>Đơn hàng</th>
                        <th>Ngày xuất</th>
                        <th>Người xuất</th>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Số Lô</th>
                        <th>Mã Thùng</th>
                        <th>Số cái xuất</th>
                        <th>Vị trí lấy hàng</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($reportData->isEmpty())
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                Không tìm thấy lô hàng xuất kho nào khớp với điều kiện lọc.
                            </td>
                        </tr>
                    @else
                        @foreach ($reportData as $row)
                            <tr>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row->issue_code }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $row->order_number ?: '-' }}</td>
                                <td style="text-align: center; white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($row->issue_date)) }}</td>
                                <td>{{ $row->creator_name }}</td>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row->sku }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $row->lot_number ?: '-' }}</td>
                                <td style="font-family: monospace; text-align: center; color: var(--accent);">{{ $row->carton_code }}</td>
                                <td class="num-cell" style="color: var(--warning); font-weight: 600;">{{ number_format($row->pieces_issued) }}</td>
                                <td style="text-align: center; white-space: nowrap; font-weight: 500; color: var(--warning);">
                                    Dãy {{ $row->zone }} - {{ $row->rack }} - {{ $row->level }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

        @elseif ($tab == 'inventory')
            <!-- 4. Inventory Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th>Mã Thùng</th>
                        <th>Mã SKU</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Số Lô</th>
                        <th>Đơn giá</th>
                        <th>Tồn Ban Đầu</th>
                        <th>Tồn Hiện Tại</th>
                        <th>Thành Tiền Tồn</th>
                        <th>Ngày Nhận</th>
                        <th>Vị trí Kệ</th>
                        <th>Định Mức</th>
                        <th>Tình trạng</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($reportData->isEmpty())
                        <tr>
                            <td colspan="13" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                Không tìm thấy thùng hàng nào đang lưu trữ khớp với bộ lọc.
                            </td>
                        </tr>
                    @else
                        @foreach ($reportData as $row)
                            @php
                                $statusText = "Bình thường";
                                $statusClass = "badge-normal";
                                if ($row->current_pieces < $row->min_stock) {
                                    $statusText = "Tồn ít";
                                    $statusClass = "badge-low";
                                } elseif ($row->current_pieces > $row->max_stock) {
                                    $statusText = "Vượt định mức";
                                    $statusClass = "badge-over";
                                }
                            @endphp
                            <tr>
                                <td style="font-family: monospace; font-weight: 700; color: white; text-align: center;">{{ $row->carton_code }}</td>
                                <td style="font-weight: 700; color: white; text-align: center;">{{ $row->sku }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td style="color: var(--text-muted); text-align: center;">{{ $row->category ?: '-' }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $row->lot_number }}</td>
                                <td class="num-cell">{{ number_format($row->price) }}đ</td>
                                <td class="num-cell">{{ number_format($row->original_pieces) }}</td>
                                <td class="num-cell" style="color: var(--accent); font-weight: 600;">{{ number_format($row->current_pieces) }}</td>
                                <td class="num-cell" style="font-weight: 700;">{{ number_format($row->current_pieces * $row->price) }}đ</td>
                                <td style="text-align: center; white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($row->received_at)) }}</td>
                                <td style="text-align: center; font-weight: 500; color: var(--accent);">
                                    Dãy {{ $row->zone }} - {{ $row->rack }} - {{ $row->level }}
                                </td>
                                <td style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                                    {{ number_format($row->min_stock) }} / {{ number_format($row->max_stock) }}
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-status {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

        @elseif ($tab == 'occupancy')
            <!-- 5. Occupancy / Space Capacity Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th>Dãy</th>
                        <th>Số Kệ</th>
                        <th>Tầng</th>
                        <th>Barcode Vị trí</th>
                        <th>Trạng thái</th>
                        <th>Số loại sản phẩm đang chứa</th>
                        <th>Số lượng Thùng chứa</th>
                        <th>Tổng số Cái lưu trữ</th>
                        <th>Tình trạng sức chứa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        @php
                            $rateText = "Trống";
                            $rowColor = "";
                            if ($row->total_cartons > 0) {
                                $rateText = "Đang chứa hàng";
                                $rowColor = "rgba(99, 102, 241, 0.03)";
                            }
                        @endphp
                        <tr style="background: {{ $rowColor }};">
                            <td style="font-weight: 700; color: white; text-align: center;">Dãy {{ $row->zone }}</td>
                            <td style="text-align: center;">{{ $row->rack }}</td>
                            <td style="text-align: center;">{{ $row->level }}</td>
                            <td style="font-family: monospace; text-align: center; font-weight: 600; color: var(--accent);">{{ $row->location_barcode }}</td>
                            <td style="text-align: center;">
                                @if($row->is_active)
                                    <span style="color: var(--success); font-weight: 500;"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>
                                @else
                                    <span style="color: var(--danger); font-weight: 500;"><i class="fa-solid fa-circle-xmark"></i> Khóa</span>
                                @endif
                            </td>
                            <td class="num-cell" style="text-align: center;">{{ $row->total_products }} loại</td>
                            <td class="num-cell" style="text-align: center; font-weight: 600;">{{ $row->total_cartons }} thùng</td>
                            <td class="num-cell" style="text-align: center; color: var(--accent); font-weight: 600;">{{ number_format($row->total_pieces) }} PCS</td>
                            <td style="text-align: center; font-weight: 600;">
                                @if ($row->total_cartons == 0)
                                    <span style="color: var(--text-muted); opacity: 0.6;">Trống</span>
                                @else
                                    <span style="color: var(--success);">Đang chứa</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif ($tab == 'audit')
            <!-- 6. Audit Logs Table -->
            <table class="table report-table" style="width: 100%;">
                <thead>
                    <tr class="report-header-group">
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Hành động</th>
                        <th>Bảng tác động</th>
                        <th>Mã bản ghi (ID)</th>
                        <th>Chi tiết thay đổi</th>
                        <th>Địa chỉ IP</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($reportData->isEmpty())
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                Không tìm thấy hoạt động nào phù hợp điều kiện lọc.
                            </td>
                        </tr>
                    @else
                        @foreach ($reportData as $log)
                            @php 
                                $badgeClass = 'badge-create';
                                if ($log->action == 'UPDATE') $badgeClass = 'badge-update';
                                if ($log->action == 'DELETE') $badgeClass = 'badge-delete';

                                $desc = "";
                                if ($log->table_name == 'products') {
                                    $desc = "Sản phẩm SKU: <strong>" . ($log->new_values['sku'] ?? $log->old_values['sku'] ?? '') . "</strong> - " . ($log->new_values['name'] ?? $log->old_values['name'] ?? '');
                                } elseif ($log->table_name == 'receipts') {
                                    $desc = "Phiếu nhập: <strong>" . ($log->new_values['receipt_code'] ?? $log->old_values['receipt_code'] ?? '') . "</strong>";
                                } elseif ($log->table_name == 'issues') {
                                    $desc = "Phiếu xuất: <strong>" . ($log->new_values['issue_code'] ?? $log->old_values['issue_code'] ?? '') . "</strong>";
                                } else {
                                    $desc = "Chi tiết: " . (json_encode($log->new_values) ?: json_encode($log->old_values) ?: '-');
                                }
                            @endphp
                            <tr>
                                <td style="white-space: nowrap; text-align: center;">{{ date('d/m/Y H:i:s', strtotime($log->created_at)) }}</td>
                                <td>
                                    <strong style="color: white;">{{ $log->user->full_name ?? 'Hệ thống' }}</strong>
                                    <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">@{{ $log->user->username ?? 'system' }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-status {{ $badgeClass }}">{{ $log->action }}</span>
                                </td>
                                <td style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted); text-align: center;">{{ $log->table_name }}</td>
                                <td style="font-family: monospace; text-align: center;">{{ $log->record_id ?: '-' }}</td>
                                <td>{!! $desc !!}</td>
                                <td style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted); text-align: center;">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
