@extends('layouts.app', ['title' => 'Chi tiết phiếu nhập kho'])

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

    /* Lot item block */
    .lot-item-block {
        border: 1px solid var(--border-color);
        border-radius: 16px;
        background: rgba(30, 41, 59, 0.2);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .lot-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .lot-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
    }

    .lot-meta {
        display: flex;
        gap: 1.5rem;
        font-size: 0.85rem;
    }

    .carton-status-in-stock {
        color: var(--success);
        background: rgba(16, 185, 129, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .carton-status-exported {
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.05);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <div>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary); text-transform: uppercase;">Chi tiết phiếu nhập kho</span>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">{{ $receipt->receipt_code }}</h3>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('inbound.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
            
            @if (in_array(Auth::user()->role->name, ['admin', 'staff']))
                <a href="{{ route('inbound.edit', ['id' => $receipt->id]) }}" class="btn btn-secondary" style="color: var(--warning); border-color: rgba(245, 158, 11, 0.2);">
                    <i class="fa-solid fa-pen"></i> Sửa phiếu
                </a>
                @if ($is_editable)
                    <a href="{{ route('inbound.destroy', ['id' => $receipt->id]) }}" class="btn btn-secondary" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu nhập kho này? Toàn bộ thùng hàng liên quan sẽ bị xóa khỏi vị trí lưu trữ.')">
                        <i class="fa-solid fa-trash"></i> Xóa phiếu
                    </a>
                @endif
            @endif

            <button class="btn btn-secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> In chứng từ</button>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Đơn đặt hàng (PO #)</span>
            <span class="info-value">{{ $receipt->po_number ?: 'Không có' }}</span>
        </div>
        
        <div class="info-item">
            <span class="info-label">Ngày nhập kho thực tế</span>
            <span class="info-value">{{ date('d/m/Y H:i', strtotime($receipt->receipt_date)) }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Thủ kho thực hiện</span>
            <span class="info-value">{{ $receipt->creator->full_name }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Trạng thái</span>
            <div><span class="badge badge-success" style="background: rgba(16, 185, 129, 0.15); color: var(--success); font-size: 0.8rem;">Đã hoàn thành</span></div>
        </div>

        <div class="info-item" style="grid-column: span 2;">
            <span class="info-label">Ghi chú phiếu</span>
            <span class="info-value" style="font-weight: 400; font-size: 0.95rem; color: var(--text-muted);">
                {{ $receipt->note ?: 'Không ghi chú' }}
            </span>
        </div>
    </div>

    <!-- List of product details and cartons -->
    <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: var(--accent);">Chi tiết lô hàng & Thùng hàng</h4>

    @foreach ($details as $detail)
        <div class="lot-item-block">
            <div class="lot-header">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">Mặt hàng</span>
                    <div class="lot-title">{{ $detail->product->name }} (SKU: {{ $detail->product->sku }})</div>
                </div>
                <div class="lot-meta">
                    <div>
                        <span style="color: var(--text-muted);">Số Lô:</span> <strong>{{ $detail->lot_number }}</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Tổng số thùng:</span> <strong>{{ $detail->total_cartons }} thùng</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Tổng số cái:</span> <strong style="color: var(--accent);">{{ number_format($detail->total_pieces) }} cái</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Đơn giá:</span> <strong>{{ number_format($detail->price) }} đ</strong>
                    </div>
                </div>
            </div>

            <!-- Cartons Sub-table -->
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 1px dashed var(--border-color); color: var(--text-muted);">
                        <th style="padding: 0.5rem; font-weight: 600;">Mã thùng hàng</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Số lượng cái ban đầu</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Tồn hiện tại trong thùng</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Vị trí (Dãy - Kệ - Tầng)</th>
                        <th style="padding: 0.5rem; font-weight: 600;">Mã vị trí (Barcode)</th>
                        <th style="padding: 0.5rem; font-weight: 600; text-align: right;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail->cartons as $carton)
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.03);">
                            <td style="padding: 0.5rem; font-family: monospace; font-weight: 600; color: white;">
                                {{ $carton->carton_code }}
                            </td>
                            <td style="padding: 0.5rem;">{{ number_format($carton->original_pieces) }} cái</td>
                            <td style="padding: 0.5rem; font-weight: 700; color: var(--accent);">
                                {{ number_format($carton->current_pieces) }} cái
                            </td>
                            <td style="padding: 0.5rem;">
                                Dãy {{ $carton->location->zone }} - {{ $carton->location->rack }} - {{ $carton->location->level }}
                            </td>
                            <td style="padding: 0.5rem; font-family: monospace;">{{ $carton->location->barcode }}</td>
                            <td style="padding: 0.5rem; text-align: right;">
                                @if ($carton->status == 'IN_STOCK')
                                    <span class="carton-status-in-stock">Trong kho</span>
                                @else
                                    <span class="carton-status-exported">Đã xuất</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
