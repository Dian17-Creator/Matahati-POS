<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stok Keluar #{{ $stockOut->cno_stock_out }}</title>
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

        /* Kop / Header Format Kita */
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

        /* Tabel Produk (3 Kolom Sesuai Olsera) */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 20px;
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
    {{-- 1. Kop Dokumen Format Kita dengan No. Stok Keluar --}}
    <div class="header">
        <h2>BUKTI PENGELUARAN BARANG (STOK KELUAR)</h2>
        <div class="doc-number">No. Stok Keluar #{{ $stockOut->cno_stock_out }}</div>
    </div>

    {{-- 2. Blok Info Metadata Transaksi --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockOut->dstock_out ? \Carbon\Carbon::parse($stockOut->dstock_out)->format('d-M-Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Transfer ke toko</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockOut->targetOutlet ? $stockOut->targetOutlet->cname : ($stockOut->creason ?: '-') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Posted</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockOut->user?->cemail ?: ($stockOut->user?->email ?: ($stockOut->user?->cname ?: '-')) }}</td>
        </tr>
    </table>

    {{-- 3. Tabel Item Produk (3 Kolom Sederhana: #, Produk, Qty) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 80%; text-align: left;">Produk</th>
                <th style="width: 14%; text-align: right;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockOut->details as $idx => $d)
            @php
                $isIng = $d->isIngredient();
                $name = $d->product?->cname ?? ($isIng ? ($d->ingredient?->cname ?? 'Bahan Baku') : 'Produk');
                $qty = is_numeric($d->nqty) ? (fmod((float)$d->nqty, 1) === 0.0 ? (int)$d->nqty : (float)$d->nqty) : $d->nqty;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="text-align: left;">{{ $name }}</td>
                <td style="text-align: right;">{{ $qty }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 15px;">Tidak ada item.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 4. Catatan di Bawah Tabel --}}
    <div class="bottom-note">
        <strong>Catatan :</strong> {{ $stockOut->cnote ?: '-' }}
    </div>
</body>
</html>
