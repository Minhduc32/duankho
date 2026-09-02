@extends('layouts.app', ['title' => 'Chi tiết phiếu xuất kho'])

@push('styles')
<style>
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
    }

    .info-value {
        font-size: 1.05rem;
        font-weight: 600;
        color: white;
    }

    .issue-item-block {
        border: 1px solid var(--border-color);
        border-radius: 16px;
        background: rgba(30, 41, 59, 0.2);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .issue-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .issue-item-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
    }

    .issue-item-meta {
        display: flex;
        gap: 1.5rem;
        font-size: 0.85rem;
    }

    .print-only {
        display: none;
    }

    @media print {
        body {
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        .sidebar, .top-header, .btn, .no-print, #toast-container, .notif-wrapper {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .card {
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            color: #000000 !important;
        }
        .print-only {
            display: block !important;
        }
        .issue-item-block {
            background: #ffffff !important;
            border: 1px solid #333 !important;
            color: #000 !important;
            page-break-inside: avoid;
            margin-bottom: 1rem !important;
        }
        .issue-item-header {
            border-bottom: 1px solid #333 !important;
        }
        .issue-item-title, .info-value, strong {
            color: #000000 !important;
        }
        .info-label {
            color: #555555 !important;
        }
        .table th, .table td {
            color: #000000 !important;
            border-bottom: 1px solid #ddd !important;
            padding: 0.4rem !important;
        }
        .table th {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="card">
    <!-- Print-only Header -->
    <div class="print-only" style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <h4 style="font-size: 1rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.2rem;">HỆ THỐNG KHO VẬN THÔNG MINH WMS</h4>
                <p style="font-size: 0.8rem; color: #555;">Địa chỉ: Khu Công Nghiệp Tân Bình, TP. Hồ Chí Minh</p>
                <p style="font-size: 0.8rem; color: #555;">Hotline: 1900 6868 &middot; Email: kho@duankho.vn</p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 0.85rem; font-weight: 700;">Mẫu số: 02 - VT</p>
                <p style="font-size: 0.75rem; color: #666;">(Theo TT 200/2014/TT-BTC)</p>
            </div>
        </div>
        <div style="text-align: center; margin-bottom: 1.5rem; border-bottom: 2px solid #000; padding-bottom: 0.75rem;">
            <h2 style="font-size: 1.5rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.25rem;">PHIẾU XUẤT KHO</h2>
            <p style="font-size: 0.9rem;">Số phiếu: <strong>{{ $issue->issue_code }}</strong> &middot; Ngày xuất: {{ date('d/m/Y H:i', strtotime($issue->issue_date)) }}</p>
        </div>
    </div>

    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <div>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary); text-transform: uppercase;">Chi tiết phiếu xuất kho</span>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">{{ $issue->issue_code }}</h3>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('outbound.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
            
            @if (in_array(Auth::user()->role->name, ['admin', 'staff']))
                <a href="{{ route('outbound.edit', ['id' => $issue->id]) }}" class="btn btn-secondary" style="color: var(--warning); border-color: rgba(245, 158, 11, 0.2);">
                    <i class="fa-solid fa-pen"></i> Sửa phiếu
                </a>
                <a href="{{ route('outbound.destroy', ['id' => $issue->id]) }}" class="btn btn-secondary" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu xuất kho này? Hàng hóa sẽ được khôi phục về các thùng chứa ban đầu.')">
                    <i class="fa-solid fa-trash"></i> Xóa phiếu
                </a>
            @endif

            <button class="btn btn-secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> In phiếu xuất</button>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Đơn hàng xuất (SO #)</span>
            <span class="info-value">{{ $issue->order_number ?: 'Không có' }}</span>
        </div>
        
        <div class="info-item">
            <span class="info-label">Ngày xuất kho thực tế</span>
            <span class="info-value">{{ date('d/m/Y H:i', strtotime($issue->issue_date)) }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Nhân viên thực hiện</span>
            <span class="info-value">{{ $issue->creator->full_name }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Trạng thái</span>
            <div><span class="badge badge-success" style="background: rgba(99, 102, 241, 0.15); color: var(--primary); font-size: 0.8rem;">Đã xuất kho</span></div>
        </div>

        <div class="info-item" style="grid-column: span 2;">
            <span class="info-label">Ghi chú phiếu</span>
            <span class="info-value" style="font-weight: 400; font-size: 0.95rem; color: var(--text-muted);">
                {{ $issue->note ?: 'Không ghi chú' }}
            </span>
        </div>
    </div>

    <!-- Details and allocations -->
    <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: var(--accent);">Phân bổ lấy hàng thực tế</h4>

    @foreach ($details as $detail)
        <div class="issue-item-block">
            <div class="issue-item-header">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">Mặt hàng xuất</span>
                    <div class="issue-item-title">{{ $detail->product->name }} (SKU: {{ $detail->product->sku }})</div>
                </div>
                <div class="issue-item-meta">
                    <div>
                        <span style="color: var(--text-muted);">Số lượng yêu cầu:</span> <strong>{{ number_format($detail->requested_pieces) }} cái</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Thực xuất:</span> <strong style="color: var(--success);">{{ number_format($detail->actual_pieces) }} cái</strong>
                    </div>
                </div>
            </div>

            <!-- Allocations table -->
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 1px dashed var(--border-color); color: var(--text-muted);">
                        <th style="padding: 0.5rem; font-weight: 600;">Lấy từ thùng hàng</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Mã Lô gốc</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Vị trí lấy hàng (Dãy - Kệ - Tầng)</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Mã vạch vị trí</th>
                        <th style="padding: 0.5rem; font-weight: 600; text-align: right;">Số cái cần lấy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail->allocations as $alloc)
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.03);">
                            <td style="padding: 0.5rem; font-family: monospace; font-weight: 600; color: white;">
                                {{ $alloc->carton_code }}
                            </td>
                            <td style="padding: 0.5rem; font-family: monospace;">{{ $alloc->lot_number ?: '-' }}</td>
                            <td style="padding: 0.5rem;">
                                Dãy {{ $alloc->zone }} - {{ $alloc->rack }} - {{ $alloc->level }}
                            </td>
                            <td style="padding: 0.5rem; font-family: monospace;">{{ $alloc->zone }}{{ str_replace('Kệ ', '', $alloc->rack) }}{{ str_replace('Tầng ', '', $alloc->level) }}</td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: var(--accent);">
                                {{ number_format($alloc->pieces_issued) }} cái
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <!-- Print-only Signatures -->
    <div class="print-only" style="margin-top: 3rem; page-break-inside: avoid;">
        <div style="display: flex; justify-content: space-between; text-align: center;">
            <div style="width: 23%;">
                <p style="font-weight: 700; margin-bottom: 0.25rem;">Người lập phiếu</p>
                <p style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký, họ tên)</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: 600;">{{ Auth::user()->full_name }}</p>
            </div>
            <div style="width: 23%;">
                <p style="font-weight: 700; margin-bottom: 0.25rem;">Người nhận hàng</p>
                <p style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký, họ tên)</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: 600;">................................</p>
            </div>
            <div style="width: 23%;">
                <p style="font-weight: 700; margin-bottom: 0.25rem;">Thủ kho</p>
                <p style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký, họ tên)</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: 600;">{{ $issue->creator->full_name }}</p>
            </div>
            <div style="width: 23%;">
                <p style="font-weight: 700; margin-bottom: 0.25rem;">Kế toán trưởng</p>
                <p style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký, họ tên)</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: 600;">................................</p>
            </div>
        </div>
    </div>
</div>
@endsection
