<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $salesH->cnotransaction }}</title>
    <style>
        @page {
            margin: 4mm 5mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace, 'DejaVu Sans Mono', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #111;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .divider {
            border-top: 1px dashed #444;
            margin: 6px 0;
        }

        .meta-table, .item-table, .calc-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 9pt;
        }

        .meta-label {
            width: 72px;
            color: #333;
        }

        .item-block {
            margin-bottom: 5px;
        }

        .item-name {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }

        .item-detail {
            width: 100%;
            font-size: 9pt;
        }

        .calc-table td {
            padding: 2px 0;
            font-size: 9pt;
        }

        .grand-total {
            font-size: 10pt;
            font-weight: bold;
        }

        .status-badge {
            margin: 12px 0 6px 0;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            letter-spacing: 1px;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 8pt;
            color: #555;
        }
    </style>
</head>
<body>
    <!-- Header / Outlet Name -->
    <div class="text-center title">
        {{ $salesH->outlet->cname ?? 'MATAHATI POS' }}
    </div>

    <!-- Metadata Transaksi -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">No. Trx:</td>
            <td>{{ $salesH->cnotransaction }}</td>
        </tr>
        <tr>
            <td class="meta-label">Waktu:</td>
            <td>{{ $salesH->dtransaction ? \Carbon\Carbon::parse($salesH->dtransaction)->translatedFormat('d M Y H:i') : now()->translatedFormat('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kasir:</td>
            <td>{{ $salesH->posUser->user->cname ?? $salesH->posUser->user->name ?? 'Kasir' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Order:</td>
            <td>{{ $salesH->cordertype ?? 'TAKE_AWAY' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Customer:</td>
            <td>{{ $salesH->cname_customer ?? $salesH->customer->cname ?? 'Umum' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- List Items -->
    @foreach ($salesH->details as $item)
        <div class="item-block">
            <div class="item-name">{{ $item->cname ?? ($item->product->cname ?? 'Item') }}</div>
            <table class="item-detail">
                <tr>
                    <td class="text-left">
                        {{ (int) $item->nqty }} x {{ number_format($item->nprice, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        {{ number_format($item->nsubtotal, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="divider"></div>

    <!-- Subtotal, Diskon, Pajak -->
    <table class="calc-table">
        <tr>
            <td class="text-left">Subtotal</td>
            <td class="text-right">{{ number_format($salesH->nsubtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Discount</td>
            <td class="text-right">{{ number_format($salesH->ndiscount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Tax</td>
            <td class="text-right">{{ number_format($salesH->ntax, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Grand Total, Bayar, Kembalian -->
    <table class="calc-table">
        <tr class="grand-total">
            <td class="text-left">GRAND TOTAL</td>
            <td class="text-right">{{ number_format($salesH->ngrandtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Paid</td>
            <td class="text-right">{{ number_format($salesH->npaid, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Change</td>
            <td class="text-right">{{ number_format($salesH->nchange, 0, ',', '.') }}</td>
        </tr>
    </table>

    <!-- Status Transaksi -->
    <div class="status-badge">
        Status: {{ strtoupper($salesH->cstatus ?? 'PAID') }}
    </div>

    <!-- Footer -->
    <div class="footer">
        Terima kasih atas kunjungan Anda
    </div>
</body>
</html>
