<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Opname - {{ $opname->cno_opname }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            color: #1e293b;
        }
        .info-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .items-table th, .items-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .items-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            padding-top: 60px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #0d6efd; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak Dokumen
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background-color: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <div class="header">
        <h2>LEMBAR STOK OPNAME</h2>
        <div>{{ $opname->outlet?->cname ?? 'Matahati POS' }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 18%;"><strong>No. Opname</strong></td>
            <td style="width: 32%;">: {{ $opname->cno_opname }}</td>
            <td style="width: 18%;"><strong>Tanggal Pelaksanaan</strong></td>
            <td style="width: 32%;">: {{ \Carbon\Carbon::parse($opname->dopname)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Outlet</strong></td>
            <td>: {{ $opname->outlet?->cname ?? '-' }}</td>
            <td><strong>Petugas Opname</strong></td>
            <td>: {{ $opname->user?->cemail ?: ($opname->user?->email ?: ($opname->user?->cname ?: 'Admin')) }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>: {{ $opname->cstatus }}</td>
            <td><strong>Waktu Cetak</strong></td>
            <td>: {{ date('d/m/Y H:i') }}</td>
        </tr>
        @if($opname->cnote)
        <tr>
            <td><strong>Catatan</strong></td>
            <td colspan="3">: {{ $opname->cnote }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Produk / Item</th>
                <th style="width: 60px;" class="text-center">Waktu</th>
                <th style="width: 80px;" class="text-center">Qty Sistem</th>
                <th style="width: 80px;" class="text-center">Qty Aktual</th>
                <th style="width: 80px;" class="text-center">Selisih</th>
                <th style="width: 90px;" class="text-end">Harga Satuan</th>
                <th style="width: 100px;" class="text-end">Total Selisih</th>
                <th style="width: 70px;">Lokasi Rak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($opname->details as $idx => $d)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $d->item_name }}</strong>
                        <div style="font-size: 10px; color: #666;">
                            {{ $d->getItemTypeLabel() }}
                            @if($d->product?->category)
                                &bull; {{ $d->product->category->cname }}
                            @endif
                        </div>
                    </td>
                    <td class="text-center">{{ $d->dtime ?: '-' }}</td>
                    <td class="text-center">{{ number_format($d->nqty_system, 0, ',', '.') }}</td>
                    <td class="text-center"><strong>{{ number_format($d->nqty_actual, 0, ',', '.') }}</strong></td>
                    <td class="text-center">
                        @if($d->nqty_diff > 0)
                            +{{ number_format($d->nqty_diff, 0, ',', '.') }}
                        @else
                            {{ number_format($d->nqty_diff, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-center text-muted">-</td>
                    <td>{{ $d->crack ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-3">Tidak ada data item opname.</td>
                </tr>
            @endforelse
        </tbody>
        @if($opname->details->isNotEmpty())
            <tfoot>
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="3" class="text-center">TOTAL</td>
                    <td class="text-center">{{ number_format($opname->ntotal_system_qty, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($opname->ntotal_actual_qty, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($opname->ntotal_diff_qty > 0)
                            +{{ number_format($opname->ntotal_diff_qty, 0, ',', '.') }}
                        @else
                            {{ number_format($opname->ntotal_diff_qty, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-center text-muted">-</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div>Petugas Fisik / Opname</div>
                <br><br><br>
                <div>( {{ $opname->user?->cname ?: 'Admin' }} )</div>
            </td>
            <td>
                <div>Supervisor Toko</div>
                <br><br><br>
                <div>( .................................... )</div>
            </td>
            <td>
                <div>Manajer / Owner</div>
                <br><br><br>
                <div>( .................................... )</div>
            </td>
        </tr>
    </table>
</body>
</html>

