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
        body { font-family: 'Georgia', Times, serif; font-size: 14px; color: #2d3748; line-height: 1.6; padding: 40px; }
        .serif-title { font-family: 'Times New Roman', Times, serif; font-weight: normal; color: #1a202c; }
        .border-line { border-bottom: 1px solid #cbd5e0; padding-bottom: 15px; margin-bottom: 25px; }
        .table-classic { width: 100%; border-collapse: collapse; margin-top: 25px; }
        .table-classic th { border-bottom: 2px solid #2d3748; padding: 10px; text-align: left; font-family: 'Georgia', serif; font-style: italic; }
        .table-classic td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-family: 'Arial', sans-serif; font-size: 13px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

    <div class="border-line" style="overflow: hidden;">
        <div style="float: left;">
            @if($logo)
                <img src="{{ asset('storage/' . $logo) }}" style="height: 60px; margin-bottom: 10px;"><br>
            @endif
            <h1 class="serif-title" style="margin: 0; font-size: 30px; text-transform: uppercase; letter-spacing: 1px;">{{ $order->business->name }}</h1>
            <p style="font-family: Arial, sans-serif; font-size: 12px; color: #718096; margin: 5px 0 0 0;">{{ $order->business->address }}</p>
        </div>
        <div style="float: right;" class="text-right">
            <h2 class="serif-title" style="margin: 0; color: {{ $color }}; font-size: 24px;">FAKTUR RESMI</h2>
            <p style="font-family: Arial, sans-serif; font-size: 13px; margin: 5px 0 0 0;">Nota: <strong>{{ $order->order_number }}</strong></p>
        </div>
    </div>

    <table width="100%" style="font-family: 'Arial', sans-serif; font-size: 13px; margin-bottom: 30px;">
        <tr>
            <td>
                <span style="color: #718096; font-size: 11px; font-weight: bold; text-transform: uppercase;">Penerima Akhir:</span><br>
                <strong style="font-size: 14px;">{{ $order->customer->name ?? 'Pelanggan Umum' }}</strong><br>
                {{ $order->customer->address ?? '-' }}
            </td>
            <td class="text-right" valign="top">
                <span><strong>Tanggal Pembukuan:</strong> {{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</span><br>
                <span><strong>Status:</strong> {{ $order->payment_status === 'paid' ? 'LUNAS (PAID)' : 'PIUTANG (UNPAID)' }}</span>
            </td>
        </tr>
    </table>

    <table class="table-classic">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Komoditas / Produk</th>
                <th width="12%" class="text-right">Kuantitas</th>
                <th width="20%" class="text-right">Harga Unit</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <span style="font-weight: bold; font-family: Arial, sans-serif;">{{ $item->product->name }}</span>
                </td>
                <td class="text-right">{{ $item->qty_billed }} {{ $item->productUnit?->unit_name ?? 'Dasar' }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($item->qty_bonus > 0)
            <tr style="background-color: #f9fafb;">
                <td></td>
                <td style="color: #16a34a; font-style: italic; font-size: 12px;">↳ Bonus: {{ $item->product->name }} (Qty: {{ $item->qty_bonus }} {{ $item->productUnit?->unit_name ?? 'Dasar' }})</td>
                <td class="text-right" style="font-size: 11px;">-</td>
                <td class="text-right" style="font-size: 11px;">Rp 0</td>
                <td class="text-right" style="font-size: 11px;">Gratis</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <div style="float: right; width: 40%; margin-top: 20px; font-family: Arial, sans-serif;">
        <table width="100%">
            @if($show_discount)
            <tr>
                <td class="text-right" style="color: #718096; padding: 2px;">Diskon:</td>
                <td class="text-right" style="color: #dc2626; padding: 2px;">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->shipping_fee_billed > 0)
            <tr>
                <td class="text-right" style="color: #718096; padding: 2px;">Ongkir:</td>
                <td class="text-right" style="padding: 2px;">Rp {{ number_format($order->shipping_fee_billed, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="font-size: 16px; font-weight: bold;">
                <td class="text-right" style="padding-top: 10px; border-top: 1px solid #2d3748;">Total Bersih:</td>
                <td class="text-right" style="padding-top: 10px; border-top: 1px solid #2d3748; color: {{ $color }};">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 120px; clear: both; font-family: 'Arial', sans-serif; font-size: 12px;">
        <table width="100%">
            <tr>
                <td width="60%" valign="top">
                    <div style="margin-bottom: 15px;"><strong>Catatan:</strong> {{ $order->notes ?? '-' }}</div>
                    @if(isset($accounts) && count($accounts) > 0)
                        <div style="border: 1px solid #cbd5e0; padding: 10px;">
                            <strong>Rekening Pembayaran:</strong><br>
                            @foreach($accounts as $acc)
                                {{ $acc->name }} - <strong>{{ $acc->account_number ?? '-' }}</strong><br>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td width="40%" align="center">
                    <p style="margin-bottom: 50px;">Hormat Kami,</p>
                    @if($order->business->signature)
                        <img src="{{ asset('storage/' . $order->business->signature) }}" style="height: 60px; display: block; margin: 10px auto;">
                    @endif
                    <p style="font-weight: bold; text-decoration: underline; margin: 0;">{{ $order->business->signer_name ?? $order->business->name }}</p>
                    <p style="color: #718096; margin: 0;">{{ $order->business->signer_title ?? 'Manajemen' }}</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>