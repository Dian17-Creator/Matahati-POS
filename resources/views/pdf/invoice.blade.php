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
            font-size: 9.5pt;
            line-height: 1.35;
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
            font-size: 10pt;
            margin-bottom: 8px;
        }

        .divider {
            border-top: 1px dashed #444;
            margin: 6px 0;
        }

        .meta-table,
        .item-table,
        .calc-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 9pt;
        }

        .meta-label {
            width: 78px;
        }

        .queue-line {
            text-align: center;
            font-size: 9.5pt;
            margin: 4px 0 6px 0;
        }

        .item-block {
            margin-bottom: 4px;
        }

        .item-name {
            font-size: 9.5pt;
        }

        .item-detail {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .calc-table td {
            padding: 2px 0;
            font-size: 9pt;
        }

        .total-row td {
            font-weight: bold;
            font-size: 9.5pt;
        }

        .extra-meta {
            margin-top: 8px;
            font-size: 9pt;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 9pt;
            color: #111;
        }
    </style>
</head>

<body>
    <!-- 1. Judul Header -->
    @if(!empty($taxSetting?->creceipt_title))
    <div class="text-center title">
        {!! nl2br(e($taxSetting->creceipt_title)) !!}
    </div>
    @endif

    <!-- 2-5. Tanggal/Jam, Kasir, Pelanggan, Nomor Transaksi -->
    <table class="meta-table">
        <tr>
            <td colspan="2">{{ $salesH->dtransaction ? \Carbon\Carbon::parse($salesH->dtransaction)->translatedFormat('d M Y H:i') : now()->translatedFormat('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kasir</td>
            <td>: {{ $salesH->posUser->user->cname ?? $salesH->posUser->user->name ?? 'Kasir' }}</td>
        </tr>
        @php
            $custName = trim((string) ($salesH->cname_customer ?? $salesH->customer->cname ?? ''));
        @endphp
        @if($custName !== '' && strtoupper($custName) !== 'UMUM')
        <tr>
            <td class="meta-label">Pelanggan</td>
            <td>: {{ $custName }}</td>
        </tr>
        @endif
        @if(!$taxSetting?->bhide_order_no)
        <tr>
            <td colspan="2">{{ $salesH->cnotransaction }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- 6. Nomor Antrian (Tipe In/Away) -->
    @php
        $orderTypeLabel = str_replace('_', '-', strtoupper((string) ($salesH->cordertype ?? 'TAKE_AWAY')));
        $queueNumber = $salesH->nqueue ?? 1;
    @endphp
    <div class="queue-line">
        Antrian {{ $queueNumber }} ({{ $orderTypeLabel }})
    </div>

    <!-- 7. Isi Item -->
    @foreach ($salesH->details as $item)
    <div class="item-block">
        <div class="item-name">{{ $item->cname ?? ($item->product->cname ?? 'Item') }}</div>
        <table class="item-detail">
            <tr>
                <td class="text-left" style="padding-left: 14px;">
                    Rp. {{ number_format($item->nprice, 0, ',', '.') }} &nbsp;x{{ (int) $item->nqty }}
                </td>
                <td class="text-right">
                    Rp. {{ number_format($item->nsubtotal, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>
    @endforeach

    <div class="divider"></div>

    <!-- 8-9. Subtotal, Diskon, Pajak -->
    <table class="calc-table">
        <tr>
            <td class="text-left">Subtotal</td>
            <td class="text-right">Rp. {{ number_format($salesH->nsubtotal, 0, ',', '.') }}</td>
        </tr>
        @if((float) $salesH->ndiscount > 0)
        <tr>
            <td class="text-left">Diskon</td>
            <td class="text-right">-Rp. {{ number_format($salesH->ndiscount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float) $salesH->ntax > 0)
        @php
            $taxLabel = $taxSetting?->ctax_name ?: 'PPN';
            $taxPercent = (float) ($salesH->ntax_percent ?? $taxSetting?->ntax_percent ?? 0);
            if ($taxPercent > 0) {
                $taxLabel .= ' ' . rtrim(rtrim(number_format($taxPercent, 2, '.', ''), '0'), '.') . '%';
            }
        @endphp
        <tr>
            <td class="text-left">{{ $taxLabel }}</td>
            <td class="text-right">Rp. {{ number_format($salesH->ntax, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- 10-12. Total, Metode Pembayaran, Kembalian -->
    <table class="calc-table">
        <tr class="total-row">
            <td class="text-left">Total</td>
            <td class="text-right">Rp. {{ number_format($salesH->ngrandtotal, 0, ',', '.') }}</td>
        </tr>
        @if(!empty($salesH->nid_payment_dual) || (float) $salesH->ndp_amount > 0)
        <tr>
            <td class="text-left">{{ $salesH->payment->cname ?? 'DP' }}</td>
            <td class="text-right">Rp. {{ number_format($salesH->ndp_amount > 0 ? $salesH->ndp_amount : ($salesH->npaid - max(0, $salesH->npaid - $salesH->ndp_amount)), 0, ',', '.') }}</td>
        </tr>
        @if(!empty($salesH->nid_payment_dual))
        <tr>
            <td class="text-left">{{ $salesH->paymentDual->cname ?? 'Pelunasan' }}</td>
            <td class="text-right">Rp. {{ number_format(max(0, $salesH->npaid - $salesH->ndp_amount), 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float) $salesH->nremaining_amount > 0)
        <tr>
            <td class="text-left">Sisa Tagihan</td>
            <td class="text-right">Rp. {{ number_format($salesH->nremaining_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @else
        <tr>
            <td class="text-left">{{ $salesH->payment->cname ?? 'Tunai' }}</td>
            <td class="text-right">Rp. {{ number_format($salesH->npaid, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float) $salesH->nchange > 0)
        <tr>
            <td class="text-left">Kembalian</td>
            <td class="text-right">Rp. {{ number_format($salesH->nchange, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <!-- 13-14. Jumlah Item & Dilayani Oleh -->
    <div class="extra-meta">
        <div>Jumlah item : {{ (int) ($salesH->nitem ?? $salesH->details->sum('nqty')) }}</div>
        <div>Dilayani oleh : {{ $salesH->posUser->user->cname ?? $salesH->posUser->user->name ?? 'Kasir' }}</div>
    </div>

    <!-- 15. Footer -->
    @if(!empty($taxSetting?->creceipt_note))
    <div class="footer">
        {!! nl2br(e($taxSetting->creceipt_note)) !!}
    </div>
    @endif
</body>

</html>
