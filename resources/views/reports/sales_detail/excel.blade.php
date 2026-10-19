<table>
    <thead>
        <tr>
            <th colspan="15" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN RINCIAN PENJUALAN</th>
        </tr>
        <tr>
            <th colspan="15" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }} | Outlet: {{ $outletName }}</th>
        </tr>
        <tr>
            <th>Tanggal</th>
            <th>No. Pesanan</th>
            <th>Status</th>
            <th>Kasir</th>
            <th>Pelanggan</th>
            <th>Total Penjualan</th>
            <th>Void/Refund</th>
            <th>Diskon</th>
            <th>Pajak</th>
            <th>Service</th>
            <th>Pembulatan</th>
            <th>Penjualan Nett</th>
            <th>Modal</th>
            <th>Laba</th>
            <th>Deposit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $row)
            @php
                $effectiveStatus = $row->display_status ?? $row->cstatus;
                $isVoid = in_array($effectiveStatus, ['VOID', 'CANCELLED', 'REFUND']);

                if ($row->csource === 'OLSERA') {
                    $grossSalesRow = (float) $row->nsubtotal;
                    $netSalesRow = (float) $row->ngrandtotal;
                    $voidRefundRow = 0;

                    $modalValue = $row->ntotal_cost !== null ? (float) $row->ntotal_cost : 0;
                    $labaValue = $row->ntotal_profit !== null ? (float) $row->ntotal_profit : 0;
                } else {
                    $grossSalesRow = (float) $row->ngrandtotal;
                    $voidRefundRow = (float) $row->total_void_refund;
                    $netSalesRow = $isVoid ? 0 : max(0, (float) $row->ngrandtotal - $voidRefundRow);

                    if ($isVoid) {
                        $modalValue = 0;
                        $labaValue = 0;
                    } elseif ($row->min_cost < 0) {
                        $modalValue = 0;
                        $labaValue = 0;
                    } else {
                        $modalValue = (float) $row->total_cost;
                        $labaValue = $netSalesRow - (float) $row->total_cost;
                    }
                }
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->dtransaction)->format('d/m/Y H:i') }}</td>
                <td>{{ $row->cnotransaction }}</td>
                <td>{{ $effectiveStatus }}</td>
                <td>{{ $row->cashier_name ?? '-' }}</td>
                <td>{{ $row->final_customer_name }}</td>
                <td>{{ $grossSalesRow }}</td>
                <td>{{ $voidRefundRow }}</td>
                <td>{{ (float) $row->ndiscount }}</td>
                <td>{{ (float) $row->ntax }}</td>
                <td>{{ (float) $row->nservice_charge }}</td>
                <td>{{ (float) $row->nrounding }}</td>
                <td>{{ $netSalesRow }}</td>
                <td>{{ $modalValue }}</td>
                <td>{{ $labaValue }}</td>
                <td>0</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="font-weight: bold; text-align: right;">GRAND TOTAL</td>
            <td style="font-weight: bold;">{{ (float) ($summary->gross_sales ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_void_refund ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_discount ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_tax ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_service ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->total_rounding ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->net_sales ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->valid_total_cost ?? 0) }}</td>
            <td style="font-weight: bold;">{{ (float) ($summary->valid_total_laba ?? 0) }}</td>
            <td style="font-weight: bold;">0</td>
        </tr>
    </tfoot>
</table>
