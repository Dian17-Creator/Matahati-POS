<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Nama Produk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Kategori</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Outlet</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Tipe Produk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Harga Beli</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Harga POS</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Harga Online</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Stok / Qty</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Satuan</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Status</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Kombo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
            @php
                $productTypeLabel = match($product->cproduct_type ?? 'PRODUK') {
                    'SETENGAH_JADI' => 'Setengah Jadi',
                    'BAHAN_BAKU' => 'Bahan Baku',
                    default => 'Produk (Barang Jadi)',
                };

                $formatNum = static function ($value): int|float {
                    $num = (float) ($value ?? 0);
                    return fmod($num, 1) === 0.0 ? (int) $num : $num;
                };
            @endphp
            <tr>
                <td style="text-align: left;">{{ $product->cname }}</td>
                <td style="text-align: left;">{{ $product->category->cname ?? '-' }}</td>
                <td style="text-align: left;">{{ $product->outlet->cname ?? '-' }}</td>
                <td style="text-align: left;">{{ $productTypeLabel }}</td>
                <td style="text-align: right;">{{ $formatNum($product->nprice_buy ?? 0) }}</td>
                <td style="text-align: right;">{{ $formatNum($product->nprice ?? 0) }}</td>
                <td style="text-align: right;">{{ $formatNum($product->nprice_online ?? 0) }}</td>
                <td style="text-align: right;">{{ (int) ($product->nqty ?? 0) }}</td>
                <td style="text-align: left;">{{ $product->csatuan ?: 'PCS' }}</td>
                <td style="text-align: left;">{{ $product->cstatus ?? '-' }}</td>
                <td style="text-align: left;">{{ $product->fcombo ? 'Ya' : 'Tidak' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
