<table>
    <thead>
        <tr>
            <th colspan="6" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN RANGKUMAN PELANGGAN</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            <th>Pelanggan</th>
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
                $totalPenjualan = round((float) ($row->total_penjualan ?? 0), 2);
                $diskon = round((float) ($row->diskon ?? 0), 2);
                $laba = round($totalPenjualan - $diskon - $modal, 2);
            @endphp
            <tr>
                <td>{{ $row->customer_name ?? 'Unknown' }}</td>
                <td>{{ $totalPenjualan }}</td>
                <td>{{ $diskon }}</td>
                <td>{{ $modal }}</td>
                <td>{{ $laba }}</td>
                <td>{{ $row->jml_transaksi }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold; text-align: right;">GRAND TOTAL</td>
            <td style="font-weight: bold;">{{ round((float) ($grandTotals['total_penjualan'] ?? 0), 2) }}</td>
            <td style="font-weight: bold;">{{ round((float) ($grandTotals['diskon'] ?? 0), 2) }}</td>
            <td style="font-weight: bold;">{{ round((float) ($grandTotals['modal'] ?? 0), 2) }}</td>
            <td style="font-weight: bold;">{{ round((float) ($grandTotals['laba'] ?? 0), 2) }}</td>
            <td style="font-weight: bold;">{{ $grandTotals['jml_transaksi'] }}</td>
        </tr>
    </tfoot>
</table>
