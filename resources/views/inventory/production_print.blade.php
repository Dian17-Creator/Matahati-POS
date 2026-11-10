<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produksi Stok #{{ $production->cno_production }}</title>
    <style>
        @page {
            margin: 15mm 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Kop / Header Dokumen */
        .header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header h2 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header .doc-number {
            font-size: 12px;
            color: #334155;
            font-weight: normal;
        }

        /* Tabel Metadata */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-table td {
            padding: 2.5px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .meta-label {
            width: 130px;
            font-weight: bold;
            color: #0f172a;
        }

        .meta-sep {
            width: 14px;
            font-weight: bold;
            color: #0f172a;
        }

        .meta-val {
            color: #1e293b;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin: 15px 0 6px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        /* Tabel Item Produk */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 15px;
        }

        .items-table th, 
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
        }

        /* Catatan di Bawah Tabel */
        .bottom-note {
            font-size: 11px;
            color: #1e293b;
            margin-top: 15px;
        }

        .bottom-note strong {
            color: #0f172a;
        }
    </style>
</head>
<body>
    {{-- 1. Kop Dokumen --}}
    <div class="header">
        <h2>BUKTI PRODUKSI BARANG (PRODUKSI STOK)</h2>
        <div class="doc-number">No. Produksi #{{ $production->cno_production }}</div>
    </div>

    {{-- 2. Metadata Transaksi --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $production->dproduction ? \Carbon\Carbon::parse($production->dproduction)->format('d-M-Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Outlet</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $production->outlet?->cname ?: 'Outlet #' . $production->nid_outlet }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $production->cstatus }}</td>
        </tr>
        <tr>
            <td class="meta-label">Dibuat Oleh</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $production->user?->cemail ?: ($production->user?->email ?: ($production->user?->cname ?: '-')) }}</td>
        </tr>
    </table>

    {{-- 3. Tabel Hasil Produksi (PLUS) --}}
    <div class="section-title">Hasil Produksi (Stok Bertambah)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 64%; text-align: left;">Produk Jadi</th>
                <th style="width: 15%; text-align: right;">Qty</th>
                <th style="width: 15%; text-align: center;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($production->products as $idx => $prod)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="text-align: left;">{{ $prod->product?->cname ?: 'Produk #' . $prod->nid_product }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($prod->nqty, 2, ',', '.') }}</td>
                <td style="text-align: center;">{{ $prod->product?->csatuan ?: 'pcs' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 12px;">Tidak ada produk hasil.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 4. Tabel Bahan Baku yang Dipakai (MINUS) --}}
    <div class="section-title">Bahan Baku yang Digunakan (Stok Berkurang)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 47%; text-align: left;">Nama Bahan Baku</th>
                <th style="width: 16%; text-align: right;">Takaran / Unit</th>
                <th style="width: 18%; text-align: right;">Total Dipotong</th>
                <th style="width: 14%; text-align: center;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($production->materials as $idx => $mat)
            @php
            $parentProductQty = ($mat->productionProduct && (float)$mat->productionProduct->nqty > 0) ? (float)$mat->productionProduct->nqty : 1;
            $unitTakaran = (float)$mat->nqty / $parentProductQty;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="text-align: left;">
                    {{ $mat->item_name }}
                    @if($mat->productionProduct?->product)
                    <span style="color: #64748b; font-size: 10px;">(Untuk: {{ $mat->productionProduct->product->cname }})</span>
                    @endif
                    @if($mat->cnote)
                    <span style="color: #64748b; font-size: 10px;">({{ $mat->cnote }})</span>
                    @endif
                </td>
                <td style="text-align: right; color: #475569;">{{ number_format($unitTakaran, 2, ',', '.') }}</td>
                <td style="text-align: right; color: #b91c1c; font-weight: bold;">-{{ number_format($mat->nqty, 2, ',', '.') }}</td>
                <td style="text-align: center;">{{ $mat->item_unit }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 12px;">Tidak ada bahan baku.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if((float)$production->nadditional_cost > 0)
    <table class="meta-table" style="margin-top: 10px;">
        <tr>
            <td class="meta-label">Biaya Produksi Tambahan</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">Rp {{ number_format($production->nadditional_cost, 0, ',', '.') }}</td>
        </tr>
    </table>
    @endif

    {{-- 5. Catatan di Bawah Tabel --}}
    <div class="bottom-note">
        <strong>Catatan :</strong> {{ $production->cnote ?: '-' }}
    </div>
</body>
</html>

