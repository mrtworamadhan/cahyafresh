<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 10pt; color: #000; margin: 0; padding: 0; }
        .w-100 { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .kop-surat { border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        
        .table-sj { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-sj th { border: 1px solid #000; padding: 4px; text-align: center; }
        .table-sj td { border-left: 1px solid #000; border-right: 1px solid #000; padding: 3px 5px; }
        .table-sj td.border-bottom { border-bottom: 1px solid #000; }
    </style>
</head>
<body>

    <div class="kop-surat text-center">
        <div class="bold" style="font-size: 14pt;">{{ strtoupper($order->business->name) }}</div>
        <div style="font-size: 9pt;">{{ $order->business->address }} | Telp: {{ $order->business->phone ?? '-' }}</div>
    </div>

    <div class="text-center" style="margin-bottom: 10px;">
        <div class="bold" style="font-size: 12pt; text-decoration: underline;">SURAT JALAN</div>
        <div style="font-size: 9pt;">No: SJ-{{ $order->order_number }}</div>
    </div>

    <table class="w-100" style="margin-bottom: 10px; font-size: 9pt;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <strong>PENGIRIM:</strong> {{ $order->business->name }}
            </td>
            <td width="50%" style="vertical-align: top;">
                <strong>PENERIMA:</strong> {{ $order->customer->name }}<br>
                {{ $order->customer->address }}
            </td>
        </tr>
    </table>

    <table class="table-sj">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th>NAMA BARANG</th>
                <th width="15%">QTY</th>
                <th width="15%">SATUAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty_billed }}</td>
                <td class="text-center">{{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
            </tr>
            @if($item->qty_bonus > 0)
            <tr>
                <td class="text-center">#</td>
                <td style="font-style: italic;">↳ Bonus: {{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty_bonus }}</td>
                <td class="text-center">{{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
            </tr>
            @endif
            @endforeach
            <tr><td colspan="4" class="border-bottom"></td></tr>
        </tbody>
    </table>

    <table class="w-100" style="margin-top: 15px; font-size: 9pt;">
        <tr class="text-center">
            <td width="33%">Penerima,</td>
            <td width="33%">Kurir,</td>
            <td width="33%">Hormat Kami,</td>
        </tr>
        <tr>
            <td style="height: 40px;"></td> <td></td>
            <td></td>
        </tr>
        <tr class="text-center">
            <td>(__________)</td>
            <td>(__________)</td>
            <td>(__________)</td>
        </tr>
    </table>

</body>
</html>