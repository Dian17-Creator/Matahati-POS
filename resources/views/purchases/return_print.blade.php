<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retur Pembelian - {{ $return->cno_purchase }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
        }

        .print-container {
            max-width: 800px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }

        .header-title {
            color: #b63352;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .table-items thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            border-bottom: 2px solid #cbd5e1;
            padding: 10px 12px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .table-items tbody td {
            padding: 10px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            height: 100%;
        }

        .signature-box {
            text-align: center;
            margin-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #334155;
            margin-top: 60px;
            display: inline-block;
            width: 80%;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .print-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10px !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            .table-items thead th {
                background-color: #e2e8f0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center py-3 bg-light border-bottom sticky-top">
        <button onclick="window.print()" class="btn btn-danger px-4 me-2" style="background-color: #b63352; border-color: #b63352;">
            <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary px-3">
            <i class="bi bi-x-circle me-1"></i> Tutup
        </button>
    </div>

    <div class="print-container">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
            <div>
                <h3 class="header-title mb-1">MATAHATI POS</h3>
                <div class="text-muted small">Nota Retur Pembelian Barang</div>
                <div class="fw-semibold text-dark mt-1">{{ $return->outlet?->cname ?? '-' }}</div>
                @if($return->outlet && $return->outlet->caddress)
                <div class="text-muted small">{{ $return->outlet->caddress }}</div>
                @endif
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-1 text-secondary">PURCHASE RETURN</h4>
                <div class="fw-bold fs-6 text-dark">{{ $return->cno_purchase }}</div>
                <div class="text-muted small mt-1">Tanggal Retur: {{ \Carbon\Carbon::parse($return->dpurchase)->format('d M Y') }}</div>
                <div class="text-muted small">Ref PO: <strong>{{ $return->cno_purchase_ref ?: '-' }}</strong></div>
                @if($return->cno_reference)
                <div class="text-muted small">Stok Keluar: <strong>{{ $return->cno_reference }}</strong></div>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="info-card">
                    <div class="fw-bold text-secondary small mb-1">SUPPLIER</div>
                    <div class="fw-bold text-dark">{{ $return->supplier?->cname ?? '-' }}</div>
                    <div class="text-muted small">PIC: {{ $return->supplier?->ccontact_person ?: '-' }}</div>
                    <div class="text-muted small">Telp: {{ $return->supplier?->cphone ?: '-' }}</div>
                    <div class="text-muted small">{{ $return->supplier?->caddress ?: '-' }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="info-card">
                    <div class="fw-bold text-secondary small mb-1">INFO DOKUMEN</div>
                    <div class="small">Status: <strong>{{ $return->cstatus }}</strong></div>
                    <div class="small">Pembayaran / Refund: <strong>{{ $return->cpayment_status === 'PAID' ? 'Selesai' : 'Belum' }}</strong></div>
                    @if($return->dpayment)
                    <div class="small">Tanggal Bayar: {{ \Carbon\Carbon::parse($return->dpayment)->format('d M Y') }}</div>
                    @endif
                    <div class="small text-muted mt-1">Catatan: {{ $return->cnote ?: '-' }}</div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-items mb-4">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">No</th>
                    <th>Nama Barang</th>
                    <th class="text-center">Qty Retur</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-end">Harga Beli</th>
                    <th>Alasan</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->details as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $item->product?->cname ?? ($item->ingredient?->cname ?? '-') }}</td>
                    <td class="text-center fw-bold">{{ $item->nqty }}</td>
                    <td class="text-center">{{ $item->getItemUnit() }}</td>
                    <td class="text-end">Rp {{ number_format((float) $item->ncost, 0, ',', '.') }}</td>
                    <td>{{ $item->creason ?: '-' }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format((float) $item->nsubtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end fw-bold">TOTAL RETUR</td>
                    <td class="text-end fw-bold fs-6">Rp {{ number_format((float) $return->ntotal_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="row signature-box">
            <div class="col-6">
                <div class="small text-muted">Dibuat Oleh,</div>
                <div class="signature-line"></div>
                <div class="small mt-1">{{ $return->user?->cname ?? 'Admin' }}</div>
            </div>
            <div class="col-6">
                <div class="small text-muted">Diterima Supplier,</div>
                <div class="signature-line"></div>
                <div class="small mt-1">{{ $return->supplier?->cname ?? 'Supplier' }}</div>
            </div>
        </div>
    </div>
</body>
</html>

