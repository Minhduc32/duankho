@extends('layouts.app', ['title' => 'Nhật ký Hoạt động (Audit Log)'])

@push('styles')
<style>
    .log-badge {
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
</style>
@endpush

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Nhật ký hệ thống (Audit Log)</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Ghi nhận tất cả các thao tác thay đổi dữ liệu trong kho để đối soát và bảo mật.</p>
        </div>
        <div>
            <a href="{{ route('reports.index', ['tab' => 'audit']) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-file-invoice"></i> Báo cáo Audit Log
            </a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Thời gian</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Nhân viên</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Hành động</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Bảng tác động</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Mã bản ghi (ID)</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Chi tiết thay đổi</th>
                    <th style="padding: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">Địa chỉ IP</th>
                </tr>
            </thead>
            <tbody>
                @if ($logs->isEmpty())
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            Chưa có hoạt động nào trong hệ thống.
                        </td>
                    </tr>
                @else
                    @foreach ($logs as $log)
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
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem; white-space: nowrap;">{{ date('d/m/Y H:i:s', strtotime($log->created_at)) }}</td>
                            <td style="padding: 1rem;">
                                <strong style="color: white;">{{ $log->user->full_name ?? 'Hệ thống' }}</strong>
                                <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">@{{ $log->user->username ?? 'system' }}</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="log-badge {{ $badgeClass }}">{{ $log->action }}</span>
                            </td>
                            <td style="padding: 1rem; font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">{{ $log->table_name }}</td>
                            <td style="padding: 1rem; font-family: monospace; text-align: center;">{{ $log->record_id ?: '-' }}</td>
                            <td style="padding: 1rem; font-size: 0.88rem;">{!! $desc !!}</td>
                            <td style="padding: 1rem; font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
