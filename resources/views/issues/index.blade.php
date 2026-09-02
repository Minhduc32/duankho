@extends('layouts.app', ['title' => 'Xuất kho (Outbound)'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Danh sách phiếu xuất kho</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Theo dõi lịch sử xuất kho, phân bổ lô hàng và kiểm soát tồn kho xuất.</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('reports.index', ['tab' => 'outbound']) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-file-invoice"></i> Báo cáo xuất kho
            </a>
            @if (in_array(Auth::user()->role->name, ['admin', 'staff']))
                <a href="{{ route('outbound.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Lập phiếu xuất kho
                </a>
            @endif
        </div>
    </div>

    <!-- Filter / Search bar -->
    <form method="GET" action="{{ route('outbound.index') }}" style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: flex-end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Tìm mã phiếu / SO</label>
                <input type="text" name="q" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" placeholder="Nhập từ khoá..." value="{{ request('q') }}">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Từ ngày</label>
                <input type="date" name="from" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" value="{{ request('from') }}">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Đến ngày</label>
                <input type="date" name="to" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" value="{{ request('to') }}">
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Lọc
                </button>
                <a href="{{ route('outbound.index') }}" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </div>
        @if (request('q') || request('from') || request('to'))
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                Kết quả: <strong style="color: white;">{{ $issues->count() }}</strong> phiếu xuất kho
            </div>
        @endif
    </form>

    <!-- Issues Table -->
    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Mã phiếu xuất</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Mã đơn xuất (SO/PO)</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Ngày xuất</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Người thực hiện</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Ghi chú</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Trạng thái</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @if ($issues->isEmpty())
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                            Chưa có phiếu xuất kho nào được lập.
                        </td>
                    </tr>
                @else
                    @foreach ($issues as $i)
                        <tr style="border-bottom: 1px solid var(--border-color);" class="table-row">
                            <td style="padding: 1rem; font-weight: 700; color: white;">
                                <a href="{{ route('outbound.show', ['id' => $i->id]) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $i->issue_code }}
                                </a>
                            </td>
                            <td style="padding: 1rem; font-family: monospace; font-size: 0.9rem;">{{ $i->order_number ?: '-' }}</td>
                            <td style="padding: 1rem;">{{ date('d/m/Y H:i', strtotime($i->issue_date)) }}</td>
                            <td style="padding: 1rem;">{{ $i->creator->full_name }}</td>
                            <td style="padding: 1rem; color: var(--text-muted); font-size: 0.88rem;">{{ $i->note ?: '-' }}</td>
                            <td style="padding: 1rem;">
                                <span class="badge badge-success" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">Đã xuất kho</span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.4rem;">
                                    <a href="{{ route('outbound.show', ['id' => $i->id]) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                        <i class="fa-solid fa-eye"></i> Chi tiết
                                    </a>
                                    @if (in_array(Auth::user()->role->name, ['admin', 'staff']))
                                        <a href="{{ route('outbound.edit', ['id' => $i->id]) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--warning); border-color: rgba(245, 158, 11, 0.15);">
                                            <i class="fa-solid fa-pen"></i> Sửa
                                        </a>
                                        <a href="{{ route('outbound.destroy', ['id' => $i->id]) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.15);" onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu xuất kho này?')">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
