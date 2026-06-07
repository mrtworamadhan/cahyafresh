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
        <meta property="og:image" content="{{ public_path('storage/' . $logo) }}">
    @else
        <meta property="og:image" content="{{ public_path('images/brand/icon-colour.png') }}">
    @endif
    
    <meta property="og:site_name" content="{{ $order->business->name }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Invoice #{{ $order->order_number }}">
    <meta name="twitter:description" content="Tagihan dari {{ $order->business->name }}. Total: Rp {{ number_format($order->total_amount, 0, ',', '.') }}">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #27272a; line-height: 1.5; margin: 0; padding: 30px; }
        .header-table, .info-table, .items-table { width: 100%; border-collapse: collapse; }
        .header-table td { padding-bottom: 20px; border-bottom: 2px solid #e4e4e7; }
        .info-table td { padding: 20px 0; font-size: 13px; }
        
        .items-table th { background-color: {{ $color }}; color: #ffffff; padding: 12px 10px; text-align: left; font-size: 12px; text-transform: uppercase; font-weight: bold; }
        .items-table td { padding: 12px 10px; border-bottom: 1px solid #e4e4e7; font-size: 13px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        
        .status-paid { color: #16a34a; border: 1px solid #16a34a; background-color: #f0fdf4; }
        .status-unpaid { color: #dc2626; border: 1px solid #dc2626; background-color: #fef2f2; }
        
        .total-box { float: right; width: 40%; margin-top: 20px; font-size: 13px; }
        .total-box td { padding: 4px 0; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td valign="top">
                @if($logo)
                    <img src="{{ public_path('storage/' . $logo) }}" style="height: 70px; margin-bottom: 10px;">
                @else
                    <h1 style="color: {{ $color }}; margin: 0;">{{ $order->business->name }}</h1>
                @endif
                <div style="font-size: 12px; color: #71717a; margin-top: 5px;">
                    {{ $order->business->address }}<br>
                    Telp: {{ $order->business->phone ?? '-' }}
                </div>
            </td>
            <td valign="top" class="text-right">
                <h1 style="color: {{ $color }}; margin: 0; font-size: 28px; letter-spacing: -1px;">INVOICE</h1>
                <p style="margin: 5px 0 0 0; font-weight: bold; color: #52525b;">#{{ $order->order_number }}</p>
                <p style="margin: 2px 0 0 0; color: #a1a1aa; font-size: 12px;">Tanggal: {{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%" valign="top">
                <span style="color: #a1a1aa; font-weight: bold; font-size: 11px; letter-spacing: 1px;">DITAGIHKAN KEPADA:</span><br>
                <strong style="font-size: 15px; color: #18181b;">{{ $order->customer->name ?? 'Pelanggan Umum' }}</strong><br>
                <span style="color: #52525b;">{{ $order->customer->address ?? 'Alamat Umum' }}<br>Telp: {{ $order->customer->phone ?? '-' }}</span>
            </td>
            <td width="50%" valign="top" class="text-right">
                <span style="color: #a1a1aa; font-weight: bold; font-size: 11px; letter-spacing: 1px;">STATUS PEMBAYARAN:</span><br>
                <div style="margin-top: 5px;">
                    @if($order->payment_status === 'paid')
                        <span class="status-badge status-paid">Lunas</span>
                    @else
                        <span class="status-badge status-unpaid">Piutang / Belum Lunas</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Deskripsi Produk</th>
                <th width="15%" class="text-right">Satuan</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="20%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><span class="text-bold">{{ $item->product->name }}</span></td>
                <td class="text-right">{{ $item->productUnit?->unit_name ?? 'Dasar' }}</td>
                <td class="text-right">{{ $item->qty_billed }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($item->qty_bonus > 0)
            <tr style="background-color: #f0fdf4;">
                <td></td>
                <td style="color: #16a34a; font-size: 11px; font-style: italic;">↳ Bonus: {{ $item->product->name }}</td>
                <td class="text-right" style="font-size: 11px;">{{ $item->productUnit?->unit_name ?? 'Dasar' }}</td>
                <td class="text-right" style="font-size: 11px;">{{ $item->qty_bonus }}</td>
                <td class="text-right" style="font-size: 11px;">Rp 0</td>
                <td class="text-right" style="font-size: 11px;">Gratis</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%; overflow: hidden;">
        <table class="total-box">
            @if($show_discount)
            <tr>
                <td class="text-right" style="padding: 5px; color: #666;">Subtotal:</td>
                <td class="text-right" style="padding: 5px;">Rp {{ number_format($order->total_amount + $order->discount_amount - $order->shipping_fee_billed, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right" style="padding: 5px; color: red;">Diskon Global:</td>
                <td class="text-right" style="padding: 5px; color: red;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->shipping_fee_billed > 0)
            <tr>
                <td class="text-right" style="padding: 5px; color: #666;">Ongkos Kirim:</td>
                <td class="text-right" style="padding: 5px;">Rp {{ number_format($order->shipping_fee_billed, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-right" style="border-top: 2px solid {{ $color }}; padding-top: 10px; font-weight: bold;">TOTAL TAGIHAN:</td>
                <td class="text-right" style="border-top: 2px solid {{ $color }}; padding-top: 10px; font-size: 16px; font-weight: bold; color: {{ $color }};">
                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="margin-top: 50px; clear: both;">
        <tr>
            <td width="60%" valign="top">
                <div style="font-size: 12px; color: #71717a;"><strong>Catatan Internal:</strong><br>{{ $order->notes ?? '-' }}</div>
                @if(isset($accounts) && count($accounts) > 0)
                <div style="margin-top: 15px; font-size: 12px; color: #52525b; border: 1px dashed #e4e4e7; padding: 10px; border-radius: 6px; width: 90%;">
                    <strong style="color: {{ $color }};">Informasi Pembayaran:</strong><br>
                    @foreach($accounts as $acc)
                        <div style="margin-top: 5px;"><span style="font-weight: bold;">{{ $acc->name }}</span> &mdash; <strong>{{ $acc->account_number ?? '-' }}</strong></div>
                    @endforeach
                </div>
                @endif
            </td>
            <td width="40%" valign="top" class="text-center">
                <p style="margin-bottom: 5px; font-size: 13px;">Hormat Kami,</p>
                
                @if($order->business->signature)
                    <img src="{{ public_path('storage/' . $order->business->signature) }}" 
                        alt="Tanda Tangan" 
                        style="height: 60px; object-fit: contain; margin: 5px 0;">
                @else
                    <div style="height: 60px; margin: 5px 0;"></div>
                @endif

                <p style="font-weight: bold; text-decoration: underline; margin: 0;">
                    {{ $order->business->signer_name ?? $order->business->name }}
                </p>
                <p style="font-size: 11px; color: #71717a; margin: 2px 0 0 0;">
                    {{ $order->business->invoice_signer_title ?? 'Manajemen' }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>