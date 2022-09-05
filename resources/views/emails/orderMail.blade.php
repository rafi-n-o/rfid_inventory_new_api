<body>
    <h1>Order</h1>
    <b>{{ $order['email'] }}</b>
    <hr />
    <h3>Service</h3>
    <table>
        <tr>
            <td>Paket</td>
            <td>:</td>
            <td>{{ $order['service_data']->category }}</td>
        </tr>
        <tr>
            <td>Durasi</td>
            <td>:</td>
            <td>{{ $order['service_data']->month }} Bulan</td>
        </tr>
        <tr>
            <td>Harga</td>
            <td>:</td>
            <td>{{ rupiah($order['service_data']->price) }}</td>
        </tr>
        <tr>
            <td>Potongan (Diskon)</td>
            <td>:</td>
            <td>{{ $order['service_data']->discount }} %</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>:</td>
            <td>{{ rupiah($order['service_data']->total) }}</td>
        </tr>
    </table>
    <hr />
    <h3>Item</h3>
    @foreach($order['cart'] as $cart)
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
    <h2>Silahkan Melakukan Pembayaran ke {{ $order['payment_type'] }}</h2>
    <h3>Unggah Bukti Pembayaran dan Lanjutkan <a href="http://149.129.232.88/rfid_inventory_new/#/order?token={{ $order['token'] }}">disini</a></h3>
</body>