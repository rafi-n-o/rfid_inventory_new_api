<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <p>Transfer</p>
    <table>
        <tr>
            <th>No.</th>
            <th>No. Receipt</th>
            <th>At</th>
            <th>From</th>
            <th>Origin</th>
            <th>To</th>
            <th>Destination</th>
        </tr>
        {{ $i = 1 }}
        @foreach($transfers as $transfer)
        <tr>
            <td>{{ $i++ }}</td>
            <td>{{ $transfer->receipt_number }}</td>
            <td>{{ $transfer->transfer_at }}</td>
            <td>{{ $transfer->from_data->name }}</td>
            <td>{{ $transfer->origin_data->name }}</td>
            <td>{{ $transfer->to_data->name }}</td>
            <td>{{ $transfer->destination_data->name }}</td>
        </tr>
        @endforeach
    </table>
    <p>Detail</p>
    <table>
        <tr>
            <th>No.</th>
            <th>No. Receipt</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Epc</th>
        </tr>
        {{ $i = 1 }}
        @foreach($transfers as $transfer)
        @foreach($transfer->transfer_datas as $transferData)
        <tr>
            <td rowspan="{{ $transferData->qty }}">{{ $i++ }}</td>
            <td rowspan="{{ $transferData->qty }}">{{ $transferData->transfer->receipt_number}}</td>
            <td rowspan="{{ $transferData->qty }}">{{ $transferData->item_data->product->name }} {{ $transferData->item_data->attribute1_value }} {{ $transferData->item_data->attribute2_value }} {{ $transferData->item_data->attribute3_value }}</td>
            <td rowspan="{{ $transferData->qty }}">{{ $transferData->qty }}</td>
            <td>
                <table>
                    <tr></tr>
                    @foreach($transferData->epc_list as $epc)
                    <tr>
                        <td>{{ $epc }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        @endforeach
        @endforeach
    </table>
</body>

</html>