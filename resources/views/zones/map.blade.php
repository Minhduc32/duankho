@extends('layouts.app', ['title' => 'Sơ đồ 7 Dãy Kho'])

@push('styles')
<style>
    .zone-selector {
        display: flex;
        gap: 0.5rem;
        background: rgba(15, 23, 42, 0.2);
        padding: 0.5rem;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .zone-btn {
        flex: 1;
        min-width: 80px;
        padding: 0.75rem;
        text-align: center;
        background: none;
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 1rem;
        border-radius: 10px;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .zone-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .zone-btn:hover:not(.active) {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }

    /* Grid Layout */
    .grid-layout {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* 5 Racks */
        gap: 1.5rem;
        overflow-x: auto;
        padding-bottom: 1rem;
    }

    @media (max-width: 992px) {
        .grid-layout {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .grid-layout {
            grid-template-columns: 1fr;
        }
    }

    .rack-column {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .rack-header {
        text-align: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--accent);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.5rem;
    }

    .level-cell {
        background: rgba(30, 41, 59, 0.4);
        border: 1px dashed var(--border-color);
        border-radius: 12px;
        padding: 0.75rem;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        transition: var(--transition);
    }

    .level-cell:hover {
        border-style: solid;
        border-color: var(--primary);
        background: rgba(30, 41, 59, 0.6);
    }

    .level-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        display: flex;
        justify-content: space-between;
    }

    .barcode-badge {
        background: rgba(255, 255, 255, 0.05);
        padding: 1px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.7rem;
    }

    /* Cartons inside cells */
    .carton-item {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0.05) 100%);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 8px;
        padding: 0.5rem;
        font-size: 0.8rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .carton-item:hover {
        transform: scale(1.02);
        border-color: var(--primary);
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.15);
    }

    .carton-code {
        font-weight: 600;
        color: white;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        font-size: 0.75rem;
    }

    .carton-prod {
        color: var(--text-muted);
        font-weight: 500;
        font-size: 0.75rem;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }

    .carton-qty {
        display: flex;
        justify-content: space-between;
        font-weight: 600;
        color: var(--accent);
        margin-top: 0.25rem;
        font-size: 0.75rem;
    }

    .empty-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-grow: 1;
        color: rgba(255, 255, 255, 0.1);
        font-size: 0.8rem;
        font-style: italic;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Xem sơ đồ 7 dãy kho</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Chọn một dãy từ A đến G để xem sơ đồ kệ, tầng và các thùng hàng đang lưu trữ.</p>
        </div>
        <div>
            <a href="{{ route('reports.index', ['tab' => 'occupancy', 'zone' => $selectedZone]) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-warehouse"></i> Báo cáo hiệu suất kho
            </a>
        </div>
    </div>

    <!-- Dãy selector tabs -->
    <div class="zone-selector">
        @foreach ($zones as $z)
            <a href="{{ route('zone-map', ['zone' => $z]) }}" class="zone-btn {{ $selectedZone == $z ? 'active' : '' }}">
                Dãy {{ $z }}
            </a>
        @endforeach
    </div>

    <!-- Layout Grid: 5 columns (Racks), 4 rows (Levels) -->
    <div class="grid-layout">
        @for ($r = 1; $r <= 5; $r++)
            <div class="rack-column">
                <div class="rack-header">Kệ {{ $r }}</div>
                
                @for ($l = 4; $l >= 1; $l--) {{-- Tầng 4 trên cùng, tầng 1 dưới cùng --}}
                    @php 
                        $rackName = "Kệ " . $r;
                        $levelName = "Tầng " . $l;
                        $cell = $layoutData[$rackName][$levelName];
                    @endphp
                    <div class="level-cell">
                        <div class="level-title">
                            <span>Tầng {{ $l }}</span>
                            <span class="barcode-badge">{{ $cell['barcode'] }}</span>
                        </div>
                        
                        @if (empty($cell['cartons']))
                            <div class="empty-placeholder">Trống</div>
                        @else
                            @foreach ($cell['cartons'] as $carton)
                                <div class="carton-item" title="Sản phẩm: {{ $carton->product_name }}&#10;Mã thùng: {{ $carton->carton_code }}&#10;Lô hàng: {{ $carton->lot_number }}&#10;Tồn: {{ $carton->current_pieces }}/{{ $carton->original_pieces }} cái">
                                    <span class="carton-code"><i class="fa-solid fa-box"></i> {{ $carton->carton_code }}</span>
                                    <span class="carton-prod">{{ $carton->product_name }}</span>
                                    <div class="carton-qty">
                                        <span>Lô: {{ $carton->lot_number }}</span>
                                        <span>{{ $carton->current_pieces }} cái</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</div>
@endsection
