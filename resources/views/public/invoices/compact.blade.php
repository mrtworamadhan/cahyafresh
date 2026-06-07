<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    
    <title>Invoice #{{ $order->order_number }} - {{ $order->business->name }}</title>

    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Tagihan resmi dari {{ $order->business->name }} untuk pesanan #{{ $order->order_number }}.">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/icon-colour.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-colour.png') }}">
    <meta name="theme-color" content="{{ $color ?? '#16a34a' }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Invoice #{{ $order->order_number }} - {{ $order->business->name }}">
    <meta property="og:description" content="Total Tagihan: Rp {{ number_format($order->total_amount, 0, ',', '.') }} | Status: {{ $order->payment_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}.">
    
    @if(isset($logo) && $logo)
        <meta property="og:image" content="{{ asset('storage/' . $logo) }}">
    @else
        <meta property="og:image" content="{{ asset('images/brand/icon-colour.png') }}">
    @endif
    
    <meta property="og:site_name" content="{{ $order->business->name }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Invoice #{{ $order->order_number }}">
    <meta name="twitter:description" content="Tagihan dari {{ $order->business->name }}. Total: Rp {{ number_format($order->total_amount, 0, ',', '.') }}">
    <style>
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 11px; 
            color: #000000; 
            margin: 0; 
            padding: 2px; /* Dikecilin drastis */
            width: 100%; 
        }
        .line-dashed { border-bottom: 1px dashed #000000; margin: 5px 0; }
        .w-100 { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
    </style>
</head>
<body>

    <div class="text-center" style="margin-bottom: 10px;">
        <div class="bold" style="font-size: 20px;">{{ strtoupper($order->business->name) }}</div>
        <div style="font-size: 11px;">{{ $order->business->address }}</div>
        <div style="font-size: 11px;">Telp: {{ $order->business->phone ?? '-' }}</div>
    </div>

    <div class="line-dashed"></div>

    <table class="w-100">
        <tr>
            <td width="30%">NOTA:</td>
            <td class="text-right">#{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td>TANGGAL:</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($order->order_date)->format('d/m/y H:i') }}</td>
        </tr>
        <tr>
            <td>KLIEN:</td>
            <td class="text-right">{{ $order->customer->name ?? 'Umum' }}</td>
        </tr>
    </table>

    <div class="line-dashed"></div>

    <table class="w-100">
        <tr class="bold">
            <td width="40%">Item</td>
            <td width="15%" class="text-right">Qty</td>
            <td width="20%" class="text-right">Harga</td>
            <td width="25%" class="text-right">Sub</td>
        </tr>
        @foreach($order->orderItems as $item)
        <tr>
            <td>{{ $item->product->name }}</td>
            <td class="text-right">{{ $item->qty_billed }}</td>
            <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($item->qty_bonus > 0)
        <tr>
            <td colspan="4" style="font-size: 10px; font-style: italic;">
                ↳ Bonus: {{ $item->qty_bonus }} {{ $item->productUnit?->unit_name ?? 'Pcs' }}
            </td>
        </tr>
        @endif
        @endforeach
    </table>

    <div class="line-dashed"></div>

    <table class="w-100">
        @if($order->discount_amount > 0)
        <tr>
            <td class="text-right">Diskon:</td>
            <td class="text-right">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($order->shipping_fee_billed > 0)
        <tr>
            <td class="text-right">Ongkir:</td>
            <td class="text-right">{{ number_format($order->shipping_fee_billed, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="bold" style="font-size: 14px;">
            <td class="text-right">TOTAL:</td>
            <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line-dashed"></div>
    
    @if(isset($accounts) && count($accounts) > 0)
    <div style="font-size: 10px; margin-bottom: 5px;">
        <strong>Rekening Pembayaran:</strong><br>
        @foreach($accounts as $acc)
            {{ $acc->name }}: {{ $acc->account_number }}<br>
        @endforeach
    </div>
    @endif

    <div style="font-size: 10px; margin-top: 5px;">Catatan: {{ $order->notes ?? '-' }}</div>
    <div class="text-center" style="margin-top: 15px; font-size: 10px;">-- Terima Kasih --</div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>