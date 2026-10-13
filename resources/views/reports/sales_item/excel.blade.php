<table>
    <thead>
        <tr>
            <th colspan="11" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN ITEM PENJUALAN</th>
        </tr>
        <tr>
            <th colspan="11" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }} | Outlet: {{ $outletName }}</th>
        </tr>
        <tr>
            <th>No. Pesanan</th>
            <th>Tanggal</th>
            <th>Penjualan Oleh</th>
            <th>Item</th>
            <th>Pelanggan</th>
            <th>Qty</th>
            <th>Diskon</th>
            <th>Total Penjualan</th>
            <th>Modal Produk</th>
            <th>Laba</th>
            <th>Komisi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $row)
            @php
                $netQty = (float) ($row->nqty - $row->nqty_void - $row->nqty_refund);
                $grossSubtotal = $netQty * (float) $row->nprice;
                $prorateDiscount = ($row->transaction_subtotal > 0 && $row->transaction_discount > 0)
                    ? ($grossSubtotal / (float) $row->transaction_subtotal) * (float) $row->transaction_discount
                    : 0;
                $totalPenjualan = max(0, $grossSubtotal - $prorateDiscount);
                $totalModal = $netQty * (float) $row->ncost;
                $laba = $totalPenjualan - $totalModal;
                $itemDiscount = 0;
            @endphp
            <tr>
                <td>{{ $row->cnotransaction }}</td>
                <td>{{ \Carbon\Carbon::parse($row->dtransaction)->format('d/m/Y H:i') }}</td>
                <td>{{ $row->cashier_name ?? '-' }}</td>
                <td>{{ $row->item_name }}</td>
                <td>{{ $row->customer_name }}</td>
                <td>{{ $netQty }}</td>
                <td>{{ $itemDiscount }}</td>
                <td>{{ $totalPenjualan }}</td>
                <td>{{ $totalModal }}</td>
                <td>{{ $laba }}</td>
                <td>0</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="font-weight: bold; text-align: right;">GRAND TOTAL</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_qty ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_discount ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_penjualan ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_modal ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_laba ?? 0) }}</td>
            <td style="font-weight: bold;">0</td>
        </tr>
    </tfoot>
</table>
