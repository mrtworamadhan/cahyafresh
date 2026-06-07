<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resi Pengiriman - {{ $order->order_number }}</title>
    <style>
        /* Ukuran standar mesin termal kasir / resi logistik */
        @page { margin: 0; }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            color: #000; 
            margin: 0; 
            padding: 10px;
            width: 80mm; /* Bisa diganti ke 100mm jika pakai printer barcode yang lebih lebar */
            box-sizing: border-box;
            background-color: #fff;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-bottom { border-bottom: 2px dashed #000; margin-bottom: 10px; padding-bottom: 10px; }
        .border-solid { border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 10px; }
        
        .title { font-size: 16px; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 1px;}
        .subtitle { font-size: 11px; margin: 0 0 10px 0; }
        
        .qr-box { margin: 15px 0; }
        .qr-box img { width: 120px; height: 120px; }
        
        .pin-box { 
            border: 3px solid #000; 
            padding: 10px; 
            font-size: 24px; 
            font-weight: 900; 
            margin: 10px 0; 
            letter-spacing: 3px;
        }

        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .content-text { font-size: 14px; margin: 0 0 10px 0; line-height: 1.3; }
        .small-text { font-size: 10px; line-height: 1.2; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px;}
        table th, table td { font-size: 11px; vertical-align: top; text-align: left; padding: 2px 0;}
    </style>
</head>
<body onload="window.print()">

    <div class="text-center border-solid">
        <h1 class="title">{{ $order->business->name }}</h1>
        <p class="subtitle">{{ $order->business->address }}<br>Telp: {{ $order->business->phone ?? '-' }}</p>
    </div>

    @if($order->delivery)
    <div class="text-center border-bottom">
        <div class="section-title">Kode Pelacakan:</div>
        <div style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">{{ $order->delivery->tracking_code }}</div>
        
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(url('/courier/' . $order->delivery->tracking_code)) }}" alt="QR Code Resi">
        </div>

        <div class="section-title">PIN AKSES SUPIR (JANGAN HILANG)</div>
        <div class="pin-box">{{ $order->delivery->access_pin }}</div>
        <p class="small-text" style="margin-top: 5px;">Scan QR Code di atas menggunakan kamera HP lalu masukkan PIN ini untuk menyelesaikan pengiriman.</p>
    </div>
    @endif

    <div class="border-bottom">
        <div class="section-title">TUJUAN PENGIRIMAN:</div>
        <p class="content-text font-bold" style="font-size: 16px;">{{ $order->customer->name ?? 'Pelanggan Umum' }}</p>
        <p class="content-text">{{ $order->customer->address ?? 'Alamat tidak tersedia' }}</p>
        <p class="content-text font-bold">HP: {{ $order->customer->phone ?? '-' }}</p>
        
        <div style="margin-top: 10px;">
            <div class="section-title">EKSPEDISI / ARMADA:</div>
            <p class="content-text font-bold">
                {{ $order->delivery?->courier?->name ?? 'Kurir Internal' }}
            </p>
        </div>
    </div>

    <div class="border-solid">
        <div class="section-title">RINCIAN PESANAN (#{{ $order->order_number }})</div>
        <table>
            @foreach($order->orderItems as $item)
            <tr>
                <td width="15%">{{ $item->qty_billed }}x</td>
                <td width="85%">
                    {{ $item->product->name }}
                    @if($item->qty_bonus > 0)
                        <br><span style="font-size: 9px;">+ Bonus: {{ $item->qty_bonus }} pcs</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
        
        <div class="text-right small-text" style="margin-top: 5px;">
            Total Item: {{ $order->orderItems->sum('qty_billed') }} Barang
        </div>
    </div>

    <div class="text-center small-text">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Terima kasih atas pesanan Anda.</strong>
    </div>

</body>
</html>