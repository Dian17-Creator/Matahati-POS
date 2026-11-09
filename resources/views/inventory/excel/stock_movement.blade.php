<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Grup / Kategori</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Produk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Stok Awal</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Stok Masuk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Pengembalian</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Penjualan</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Stok Keluar</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Sisa</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Dibuat Oleh</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Dibuat Tanggal dan Jam</th>
        </tr>
    </thead>
    <tbody>
        @foreach($movements as $row)
        @php
        $fmtAwal = is_numeric($row->qty_awal) ? (fmod((float)$row->qty_awal, 1) === 0.0 ? (int)$row->qty_awal : (float)$row->qty_awal) : $row->qty_awal;
        $fmtMasuk = is_numeric($row->qty_masuk) ? (fmod((float)$row->qty_masuk, 1) === 0.0 ? (int)$row->qty_masuk : (float)$row->qty_masuk) : $row->qty_masuk;
        $fmtPengembalian = is_numeric($row->qty_pengembalian) ? (fmod((float)$row->qty_pengembalian, 1) === 0.0 ? (int)$row->qty_pengembalian : (float)$row->qty_pengembalian) : $row->qty_pengembalian;
        $fmtPenjualan = is_numeric($row->qty_penjualan) ? (fmod((float)$row->qty_penjualan, 1) === 0.0 ? (int)$row->qty_penjualan : (float)$row->qty_penjualan) : $row->qty_penjualan;
        $fmtKeluar = is_numeric($row->qty_keluar) ? (fmod((float)$row->qty_keluar, 1) === 0.0 ? (int)$row->qty_keluar : (float)$row->qty_keluar) : $row->qty_keluar;
        $fmtSisa = is_numeric($row->qty_sisa) ? (fmod((float)$row->qty_sisa, 1) === 0.0 ? (int)$row->qty_sisa : (float)$row->qty_sisa) : $row->qty_sisa;
        @endphp
        <tr>
            <td style="text-align: left;">{{ $row->category_name ?? '-' }}</td>
            <td style="text-align: left;">{{ $row->cname }}</td>
            <td style="text-align: right;">{{ $fmtAwal }}</td>
            <td style="text-align: right;">{{ $fmtMasuk }}</td>
            <td style="text-align: right;">{{ $fmtPengembalian }}</td>
            <td style="text-align: right;">{{ $fmtPenjualan }}</td>
            <td style="text-align: right;">{{ $fmtKeluar }}</td>
            <td style="text-align: right;">{{ $fmtSisa }}</td>
            <td style="text-align: left;">{{ $row->created_by ?? '-' }}</td>
            <td style="text-align: left;">{{ $row->created_time ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>