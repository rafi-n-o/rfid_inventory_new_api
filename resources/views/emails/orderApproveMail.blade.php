<body>
    <h1>Terimakasih Telah Menyelesaikan Pembayaran</h1>
    <b>{{ $details['email'] }}</b>
    <hr />
    <h3>Invoice</h3>
    <table>
        <tr>
            <td>Status</td>
            <td>:</td>
            <td>{{ $details['status'] }}</td>
        </tr>
        <tr>
            <td>Receipt Number</td>
            <td>:</td>
            <td>{{ $details['invoice']->receipt_number }}</td>
        </tr>
    </table>
    <hr />
    <h3>Service</h3>
    <table>
        <tr>
            <td>Paket</td>
            <td>:</td>
            <td>{{ $details['service_data']->category }}</td>
        </tr>
        <tr>
            <td>Durasi</td>
            <td>:</td>
            <td>{{ $details['service_data']->month }} Bulan</td>
        </tr>
        <tr>
            <td>Harga</td>
            <td>:</td>
            <td>{{ rupiah($details['service_data']->price) }}</td>
        </tr>
        <tr>
            <td>Potongan (Diskon)</td>
            <td>:</td>
            <td>{{ $details['service_data']->discount }} %</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>:</td>
            <td>{{ rupiah($details['service_data']->total) }}</td>
        </tr>
    </table>
    <hr />
    <h3>Item</h3>
    @foreach($details['cart'] as $cart)
    <table>
        <tr>
            <td>Tipe</td>
            <td>:</td>
            <td>{{ $cart->type }}</td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td>{{ $cart->name }}</td>
        </tr>
        <tr>
            <td>Harga</td>
            <td>:</td>
            <td>{{ rupiah($cart->price) }}</td>
        </tr>
        <tr>
            <td>Qty</td>
            <td>:</td>
            <td>{{ $cart->qty }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>:</td>
            <td>{{ rupiah($cart->total) }}</td>
        </tr>
    </table>
    <hr />
    @endforeach
    <h2>Terimakasih telah menyelesaikan pembayaran</h2>
    <h3>Registrasi akun dan lanjutkan <a href="http://149.129.232.88/rfid_inventory_new/#/register?token={{ $details['token'] }}">disini</a></h3>
</body>