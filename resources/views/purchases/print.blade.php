<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchase->cno_purchase }}</title>
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
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
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

        .badge-status {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-received {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-unpaid {
            background-color: #fef3c7;
            color: #92400e;
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
            .badge-status {
                border: 1px solid #64748b !important;
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
            <div>
                <h3 class="header-title mb-1">MATAHATI POS</h3>
                <div class="text-muted small">Surat Pesanan Pembelian / Barang Masuk</div>
                <div class="fw-semibold text-dark mt-1">{{ $purchase->outlet->cname ?? 'Semua Outlet' }}</div>
                @if($purchase->outlet && $purchase->outlet->caddress)
                    <div class="text-muted small">{{ $purchase->outlet->caddress }}</div>
                @endif
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-1 text-secondary">PURCHASE ORDER</h4>
                <div class="fw-bold fs-6 text-dark">{{ $purchase->cno_purchase }}</div>
                <div class="text-muted small mt-1">Tanggal: {{ \Carbon\Carbon::parse($purchase->dpurchase)->translatedFormat('d F Y') }}</div>
                <div class="mt-2">
                    @if($purchase->cstatus === 'PENDING')
                        <span class="badge-status badge-pending">STATUS: MENUNGGU PENERIMAAN</span>
                    @elseif($purchase->cstatus === 'RECEIVED')
                        <span class="badge-status badge-received">STATUS: SELESAI (DITERIMA)</span>
                    @else
                        <span class="badge-status badge-cancelled">STATUS: DIBATALKAN</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Supplier & PO Details -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="info-card">
                    <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 11px;">Kepada Supplier:</div>
                    <div class="fw-bold text-dark fs-6">{{ $purchase->supplier->cname ?? '-' }}</div>
                    @if($purchase->supplier?->ccontact_person)
                        <div class="text-muted small">PIC: {{ $purchase->supplier->ccontact_person }}</div>
                    @endif
                    @if($purchase->supplier?->cphone)
                        <div class="text-muted small">Telp: {{ $purchase->supplier->cphone }}</div>
                    @endif
                    @if($purchase->supplier?->caddress)
                        <div class="text-muted small mt-1">{{ $purchase->supplier->caddress }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="info-card">
                    <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 11px;">Informasi Dokumen:</div>
                    <table class="w-100 small">
                        <tr>
                            <td class="text-muted py-1" style="width: 45%;">No. Referensi / Surat:</td>
                            <td class="fw-semibold text-dark py-1">{{ $purchase->cno_reference ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Tgl Jatuh Tempo:</td>
                            <td class="fw-semibold text-dark py-1">
                                {{ $purchase->ddue_date ? \Carbon\Carbon::parse($purchase->ddue_date)->translatedFormat('d M Y') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Status Pembayaran:</td>
                            <td class="py-1">
                                <span class="badge-status {{ $purchase->cpayment_status === 'PAID' ? 'badge-paid' : 'badge-unpaid' }}">
                                    {{ $purchase->cpayment_status === 'PAID' ? 'LUNAS' : 'BELUM DIBAYAR' }}
                                </span>
                            </td>
                        </tr>
                        @if($purchase->cstatus === 'RECEIVED')
                        <tr>
                            <td class="text-muted py-1">Diterima Pada:</td>
                            <td class="fw-semibold text-dark py-1">
                                {{ $purchase->dreceived ? \Carbon\Carbon::parse($purchase->dreceived)->translatedFormat('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1">Diterima Oleh:</td>
                            <td class="fw-semibold text-dark py-1">{{ $purchase->creceived_by ?? '-' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Table -->
        <table class="table table-items mb-3">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 45%;">Nama Produk / Barang</th>
                    <th style="width: 15%;" class="text-end">Harga Satuan</th>
                    <th style="width: 15%;" class="text-center">Qty Pesanan</th>
                    <th style="width: 20%;" class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->details as $index => $item)
                    <tr>
                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->product?->cname ?? '-' }}</div>
                            @if($item->product && $item->product->csku)
                                <div class="text-muted small" style="font-size: 11px;">SKU: {{ $item->product->csku }}</div>
                            @endif
                        </td>
                        <td class="text-end">Rp {{ number_format($item->ncost ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center fw-bold">{{ number_format($item->nqty ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->nsubtotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada item barang dalam pesanan pembelian ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #cbd5e1;">
                    <td colspan="3" class="text-end fw-bold py-2">TOTAL ITEM & KUANTITAS:</td>
                    <td class="text-center fw-bold py-2">{{ number_format($purchase->details->sum('nqty'), 0, ',', '.') }}</td>
                    <td class="text-end fw-bold py-2 fs-6 text-danger" style="color: #b63352 !important;">
                        Rp {{ number_format($purchase->ntotal_amount ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Notes -->
        @if($purchase->cnote)
        <div class="mb-4 p-3 bg-light rounded border">
            <div class="fw-bold text-dark small mb-1">Catatan Pesanan:</div>
            <div class="text-muted small">{{ $purchase->cnote }}</div>
        </div>
        @endif

        <!-- Signatures -->
        <div class="row pt-4">
            <div class="col-4">
                <div class="signature-box">
                    <div class="text-muted small">Dibuat Oleh,</div>
                    <div class="signature-line"></div>
                    <div class="fw-semibold mt-2">{{ $purchase->user->cname ?? 'Petugas / Admin' }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <div class="text-muted small">Penerima Barang,</div>
                    <div class="signature-line"></div>
                    <div class="fw-semibold mt-2">{{ $purchase->creceived_by ?? '( .................... )' }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <div class="text-muted small">Supplier / Pengirim,</div>
                    <div class="signature-line"></div>
                    <div class="fw-semibold mt-2">{{ $purchase->supplier->ccontact_person ?? '( .................... )' }}</div>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="text-center text-muted small mt-5 pt-3 border-top" style="font-size: 11px;">
            Dicetak secara otomatis oleh sistem Matahati POS pada {{ now()->translatedFormat('d F Y, H:i') }}
        </div>
    </div>

</body>
</html>
