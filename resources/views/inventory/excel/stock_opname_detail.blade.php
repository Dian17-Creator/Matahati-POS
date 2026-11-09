<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Nama</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Qty System</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Qty Aktual</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Qty Selisih</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Harga Beli Produk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Harga Jual Produk</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Dibuat Oleh</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: left;">Dibuat Pada</th>
        </tr>
    </thead>
    <tbody>
        @php
        $defaultUser = $opname->user?->cname ?: ($opname->user?->cfullname ?: ($opname->user?->cemail ?: 'System'));
        $headerDate = $opname->dcreated
        ? \Carbon\Carbon::parse($opname->dcreated)->format('Y-m-d H:i:s')
        : ($opname->dopname ? \Carbon\Carbon::parse($opname->dopname)->format('Y-m-d H:i:s') : '-');
        @endphp
        @foreach($opname->details as $d)
        @php
        $fmtQtySystem = is_numeric($d->nqty_system) ? (fmod((float)$d->nqty_system, 1) === 0.0 ? (int)$d->nqty_system : (float)$d->nqty_system) : $d->nqty_system;
        $fmtQtyActual = is_numeric($d->nqty_actual) ? (fmod((float)$d->nqty_actual, 1) === 0.0 ? (int)$d->nqty_actual : (float)$d->nqty_actual) : $d->nqty_actual;
        $fmtQtyDiff = is_numeric($d->nqty_diff) ? (fmod((float)$d->nqty_diff, 1) === 0.0 ? (int)$d->nqty_diff : (float)$d->nqty_diff) : $d->nqty_diff;

        // Harga Beli
        $hargaBeli = 0;
        if ($d->product) {
        $hargaBeli = (float) ($d->ncost > 0 ? $d->ncost : ($d->product->nprice_buy ?: 0));
        } elseif ($d->ingredient) {
        $hargaBeli = (float) ($d->ncost > 0 ? $d->ncost : ($d->ingredient->nprice ?: 0));
        } else {
        $hargaBeli = (float) ($d->ncost ?: 0);
        }

        // Harga Jual
        $hargaJual = $d->product ? (float) ($d->product->nprice ?: 0) : 0;

        // Waktu dibuat
        $createdTime = $d->dcreated ? \Carbon\Carbon::parse($d->dcreated)->format('Y-m-d H:i:s') : $headerDate;
        @endphp
        <tr>
            <td style="text-align: left;">{{ $d->item_name }}</td>
            <td style="text-align: right;">{{ $fmtQtySystem }}</td>
            <td style="text-align: right;">{{ $fmtQtyActual }}</td>
            <td style="text-align: right;">{{ $fmtQtyDiff }}</td>
            <td style="text-align: right;">{{ $hargaBeli }}</td>
            <td style="text-align: right;">{{ $hargaJual }}</td>
            <td style="text-align: left;">{{ $defaultUser }}</td>
            <td style="text-align: left;">{{ $createdTime }}</td>
        </tr>
        @endforeach
    </tbody>
</table>