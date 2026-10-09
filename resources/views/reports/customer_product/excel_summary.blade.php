<table>
    <thead>
        <tr>
            <th colspan="7" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN RANGKUMAN PELANGGAN</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            <th>Pelanggan</th>
            <th>Total Qty</th>
            <th>Total Penjualan</th>
            <th>Diskon</th>
            <th>Modal Produk</th>
            <th>Laba</th>
            <th>Jumlah Transaksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
            @php
                $modal = 0; 
                $laba = $row->total_penjualan - $row->diskon - $modal;
            @endphp
            <tr>
                <td>{{ $row->customer_name ?? 'Unknown' }}</td>
                <td>{{ $row->qty }}</td>
                <td>{{ $row->total_penjualan }}</td>
                <td>{{ $row->diskon }}</td>
                <td>{{ $modal }}</td>
                <td>{{ $laba }}</td>
                <td>{{ $row->jml_transaksi }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold; text-align: right;">GRAND TOTAL</td>
            <td style="font-weight: bold;">{{ $grandTotals['qty'] }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['total_penjualan'] }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['diskon'] }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['modal'] }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['laba'] }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['jml_transaksi'] }}</td>
        </tr>
    </tfoot>
</table>
