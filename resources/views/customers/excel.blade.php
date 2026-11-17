<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Nama Pelanggan</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Outlet</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Status</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Tipe</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">No Member</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Gender</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">No HP</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Email</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Tanggal Lahir</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Alamat</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Kode Pos</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Kota</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Kecamatan</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Provinsi</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Negara</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Catatan</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Transaksi Terakhir</th>
        </tr>
    </thead>
    <tbody>
        @foreach($customers as $customer)
            @php
                $lastTrx = $customer->dlast_transaction ?: $customer->sales_max_dtransaction;
            @endphp
            <tr>
                <td style="text-align: left;">{{ $customer->cname }}</td>
                <td style="text-align: left;">{{ $customer->outlet?->cname ?? '-' }}</td>
                <td style="text-align: center;">{{ $customer->factive ? 'Aktif' : 'Non-Aktif' }}</td>
                <td style="text-align: left;">{{ $customer->type?->cname ?? '-' }}</td>
                <td style="text-align: left; mso-number-format:'\@';">{{ $customer->cmembership_no ?? '-' }}</td>
                <td style="text-align: center;">{{ $customer->cgender ?? '-' }}</td>
                <td style="text-align: left; mso-number-format:'\@';">{{ $customer->cphone ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->cemail ?? '-' }}</td>
                <td style="text-align: center;">{{ $customer->dbirth ? \Carbon\Carbon::parse($customer->dbirth)->format('d M Y') : '-' }}</td>
                <td style="text-align: left;">{{ $customer->caddress ?? '-' }}</td>
                <td style="text-align: left; mso-number-format:'\@';">{{ $customer->cpostal_code ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->ccity ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->cdistrict ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->cprovince ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->ccountry ?? '-' }}</td>
                <td style="text-align: left;">{{ $customer->cnotes ?? '-' }}</td>
                <td style="text-align: center;">{{ $lastTrx ? \Carbon\Carbon::parse($lastTrx)->format('d M Y H:i') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
