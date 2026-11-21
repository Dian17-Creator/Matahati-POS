<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $purchase->cno_purchase ?: ('PO-' . $purchase->nid) }}</title>
    <style>
        @page {
            margin: 12mm 15mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #64748b; }

        /* Header Kop */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #85182a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #85182a;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
        }

        .po-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .po-no {
            font-size: 12px;
            font-weight: bold;
            color: #2563eb;
        }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger  { background-color: #fee2e2; color: #b91c1c; }

        /* Two Column Layout Table */
        .info-layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            vertical-align: top;
        }

        .card-title {
            font-size: 10px;
            font-weight: bold;
            color: #85182a;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 2px 0;
            font-size: 10.5px;
            vertical-align: top;
        }

        .data-label {
            width: 110px;
            color: #64748b;
        }

        .data-val {
            color: #0f172a;
            font-weight: 500;
        }

        /* Main Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #85182a;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid #85182a;
        }

        .items-table td {
            padding: 7px 10px;
            font-size: 10.5px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .total-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 11px;
            border-top: 2px solid #cbd5e1;
            padding: 9px 10px;
        }

        /* Note Box */
        .note-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 10.5px;
        }

        /* Signatures Table */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
        }

        .signature-space {
            height: 50px;
        }

        .signature-name {
            font-weight: bold;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            display: inline-block;
            padding-top: 4px;
            min-width: 130px;
        }

        /* Footer */
        .footer-note {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- KOP HEADER -->
    <table class="kop-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="brand-title">MATAHATI POS</div>
                <div class="brand-subtitle">Surat Pesanan Pembelian (Purchase Order)</div>
                <div style="margin-top: 6px; font-weight: bold; color: #0f172a;">
                    Outlet: {{ $purchase->outlet->cname ?? 'Matahati POS' }}
                </div>
                @if($purchase->outlet && $purchase->outlet->caddress)
                    <div style="font-size: 10px; color: #64748b;">{{ $purchase->outlet->caddress }}</div>
                @endif
            </td>
            <td style="width: 45%; text-align: right; vertical-align: top;">
                <div class="po-title">PURCHASE ORDER</div>
                <div class="po-no">{{ $purchase->cno_purchase ?: ('PO-' . $purchase->nid) }}</div>
                <div style="margin-top: 4px; font-size: 10.5px; color: #475569;">
                    Tanggal: <strong>{{ \Carbon\Carbon::parse($purchase->dpurchase)->translatedFormat('d F Y') }}</strong>
                </div>
                <div style="margin-top: 6px;">
                    @php
                        $st = strtoupper((string) $purchase->cstatus);
                        $isPaid = strtoupper((string) $purchase->cpayment_status) === 'PAID';
                    @endphp
                    @if($st === 'RECEIVED')
                        <span class="badge badge-success">DITERIMA</span>
                    @elseif($st === 'CANCELLED')
                        <span class="badge badge-danger">DIBATALKAN</span>
                    @else
                        <span class="badge badge-warning">PROSES (PENDING)</span>
                    @endif

                    @if($isPaid)
                        <span class="badge badge-success">LUNAS</span>
                    @else
                        <span class="badge badge-warning">BELUM LUNAS</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- INFO METADATA LAYOUT (2 KOLOM) -->
    <table class="info-layout-table">
        <tr>
            <td style="width: 49%; vertical-align: top; padding-right: 1%;">
                <div class="info-card">
                    <div class="card-title">Informasi Supplier</div>
                    <table class="data-table">
                        <tr>
                            <td class="data-label">Nama Supplier</td>
                            <td style="width: 10px;">:</td>
                            <td class="data-val" style="font-weight: bold;">{{ $purchase->supplier->cname ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="data-label">PIC / Contact</td>
                            <td>:</td>
                            <td class="data-val">{{ $purchase->supplier->cpic ?? $purchase->supplier->ccontact_person ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="data-label">No. Telepon</td>
                            <td>:</td>
                            <td class="data-val">{{ $purchase->supplier->cphone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="data-label">Alamat</td>
                            <td>:</td>
                            <td class="data-val">{{ $purchase->supplier->caddress ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 49%; vertical-align: top; padding-left: 1%;">
                <div class="info-card">
                    <div class="card-title">Detail Dokumen</div>
                    <table class="data-table">
                        <tr>
                            <td class="data-label">No. Referensi / SJ</td>
                            <td style="width: 10px;">:</td>
                            <td class="data-val">{{ $purchase->cno_reference ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="data-label">Tgl. Jatuh Tempo</td>
                            <td>:</td>
                            <td class="data-val">
                                {{ $purchase->ddue_date ? \Carbon\Carbon::parse($purchase->ddue_date)->translatedFormat('d F Y') : 'Tidak ditentukan' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="data-label">Dibuat Oleh</td>
                            <td>:</td>
                            <td class="data-val">{{ $purchase->user->cname ?? $purchase->user->name ?? 'Admin' }}</td>
                        </tr>
                        @if($st === 'RECEIVED')
                        <tr>
                            <td class="data-label">Diterima Pada</td>
                            <td>:</td>
                            <td class="data-val">
                                {{ $purchase->dreceived ? \Carbon\Carbon::parse($purchase->dreceived)->translatedFormat('d F Y H:i') : '-' }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL ITEM PESANAN -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 45%;" class="text-left">Nama Produk / Bahan Baku</th>
                <th style="width: 12%;" class="text-center">Tipe</th>
                <th style="width: 15%;" class="text-right">Harga Satuan</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 13%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchase->details as $index => $item)
                @php
                    $prodModel = $item->product;
                    $ingModel = $item->ingredient;
                    $itemName = $prodModel ? $prodModel->cname : ($ingModel ? $ingModel->cname : '-');
                    $itemUnit = strtoupper($item->getItemUnit());
                    $itemTypeLabel = $item->getItemTypeLabel();
                    $cost = (float) ($item->ncost ?? 0);
                    $qty = (float) ($item->nqty ?? 0);
                    $subtotal = (float) ($item->nsubtotal ?? 0);
                @endphp
                <tr>
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td class="text-left">
                        <div style="font-weight: bold; color: #0f172a;">{{ $itemName }}</div>
                        @if($prodModel && $prodModel->csku)
                            <div style="font-size: 9.5px; color: #64748b;">SKU: {{ $prodModel->csku }}</div>
                        @endif
                    </td>
                    <td class="text-center text-muted" style="font-size: 9.5px;">{{ $itemTypeLabel }}</td>
                    <td class="text-right">Rp {{ number_format($cost, 0, ',', '.') }}</td>
                    <td class="text-center fw-bold">{{ number_format($qty, 0, ',', '.') }} {{ $itemUnit }}</td>
                    <td class="text-right fw-bold" style="color: #0f172a;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 15px;">Belum ada item barang dalam pesanan ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right fw-bold">TOTAL PEMBELIAN:</td>
                <td class="text-center fw-bold">{{ number_format($purchase->details->sum('nqty'), 0, ',', '.') }}</td>
                <td class="text-right fw-bold" style="color: #85182a; font-size: 12px;">
                    Rp {{ number_format((float) ($purchase->ntotal_amount ?? 0), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- CATATAN -->
    @if($purchase->cnote)
    <div class="note-box">
        <strong style="color: #0f172a;">Catatan Pesanan:</strong><br>
        <span class="text-muted">{{ $purchase->cnote }}</span>
    </div>
    @endif

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="text-muted" style="font-size: 10px;">Dibuat Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $purchase->user->cname ?? $purchase->user->name ?? 'Admin' }}</div>
            </td>
            <td>
                <div class="text-muted" style="font-size: 10px;">Penerima Barang,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $purchase->creceived_by ?: '( .................... )' }}</div>
            </td>
            <td>
                <div class="text-muted" style="font-size: 10px;">Supplier / Pengirim,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $purchase->supplier->cpic ?? $purchase->supplier->ccontact_person ?? '( .................... )' }}</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER NOTICE -->
    <div class="footer-note">
        Dokumen ini dicetak secara otomatis dari sistem Matahati POS Backoffice pada {{ now()->translatedFormat('d F Y, H:i') }}
    </div>

</body>
</html>
