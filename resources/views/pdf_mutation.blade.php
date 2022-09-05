<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <p>Mutasi</p>
    <table border="1">
        <tr>
            <th>No.</th>
            <th>Warehouse</th>
            <th>No. Receipt</th>
            <th>At</th>
            <th>From</th>
            <th>To</th>
        </tr>
        {{ $i = 1 }}
        @foreach($mutations as $mutation)
        <tr>
            <td>{{ $i++ }}</td>
            <td>{{ $mutation->warehouse_data->name}}</td>
            <td>{{ $mutation->receipt_number }}</td>
            <td>{{ $mutation->at }}</td>
            @if($mutation->type === "outbound")
            <td>{{ $mutation->from_data->name}} [outbound]</td>
            @else
            <td>{{ $mutation->from_data->name}}</td>
            @endif
            @if($mutation->type === "inbound")
            <td>{{ $mutation->to_data->name}} [inbound]</td>
            @else
            <td>{{ $mutation->to_data->name}}</td>
            @endif
        </tr>
        @endforeach
    </table>
    <p>Detail</p>
    <table border="1">
        <tr>
            <th>No.</th>
            <th>No. Receipt</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Epc</th>
        </tr>
        {{ $i = 1 }}
        @foreach($mutations as $mutation)
        @foreach($mutation->mutation_datas as $mutationData)
        <tr>
            <td>{{ $i++ }}</td>
            <td>{{ $mutationData->mutation->receipt_number }}</td>
            <td>{{ $mutationData->item_data->product->name }} {{ $mutationData->item_data->attribute1_value }} {{ $mutationData->item_data->attribute2_value }} {{ $mutationData->item_data->attribute3_value }}</td>
            <td>{{ $mutationData->qty }}</td>
            <td>
                <table border="0">
                    @foreach($mutationData->epc_list as $epc)
                    <tr>
                        <td>{{ $epc}}</td>
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