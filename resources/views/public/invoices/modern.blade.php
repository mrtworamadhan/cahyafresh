<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    
    <title>Invoice #{{ $order->order_number }} - {{ $order->business->name }}</title>

    <!-- Ubah robots menjadi noindex saja agar Google tidak mengindeks, tapi bot WA masih bisa lewat -->
    <meta name="robots" content="noindex">
    <meta name="description" content="Tagihan resmi dari {{ $order->business->name }} untuk pesanan #{{ $order->order_number }}.">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/icon-colour.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-colour.png') }}">
    <meta name="theme-color" content="{{ $color ?? '#16a34a' }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Invoice #{{ $order->order_number }} - {{ $order->business->name }}">
    <meta property="og:description" content="Total Tagihan: Rp {{ number_format($order->total_amount, 0, ',', '.') }} | Status: {{ $order->payment_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}.">
    
    <!-- Perbaikan OG Image agar lebih ramah WhatsApp -->
    @if(isset($logo) && $logo)
        <meta property="og:image" itemprop="image" content="{{ asset('storage/' . $logo) }}">
        <meta property="og:image:secure_url" content="{{ asset('storage/' . $logo) }}">
    @else
        <meta property="og:image" itemprop="image" content="{{ asset('images/brand/icon-colour.png') }}">
        <meta property="og:image:secure_url" content="{{ asset('images/brand/icon-colour.png') }}">
    @endif
    
    <meta property="og:site_name" content="{{ $order->business->name }}">

    <!-- Perbaikan Twitter Image agar ikut dinamis -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Invoice #{{ $order->order_number }}">
    <meta name="twitter:description" content="Tagihan dari {{ $order->business->name }}. Total: Rp {{ number_format($order->total_amount, 0, ',', '.') }}">
    @if(isset($logo) && $logo)
        <meta name="twitter:image" content="{{ asset('storage/' . $logo) }}">
    @else
        <meta name="twitter:image" content="{{ asset('images/brand/icon-colour.png') }}">
    @endif

    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-bottom: 3px solid {{ $color }}; padding-bottom: 20px; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background-color: {{ $color }}; color: #fff; padding: 10px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .total-section { margin-top: 20px; text-align: right; }
        .total-amount { font-size: 20px; font-weight: bold; color: {{ $color }}; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="70%" valign="top">
                @if($logo)
                    <img src="{{ (isset($is_pdf) && $is_pdf) ? public_path('storage/' . $logo) : asset('storage/' . $logo) }}" 
                        style="height: 60px; margin-bottom: 10px;">
                @endif
                <div style="font-size: 22px; color: #444;">
                    <strong>{{ $order->business->name }}</strong><br>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    {{ $order->business->address }}<br>
                    Telp: {{ $order->business->phone ?? '-' }}
                </div>
            </td>
            <td width="30%" valign="top" class="text-right">
                <h1 style="color: {{ $color }}; margin: 0; font-size: 32px;">INVOICE</h1>
                <p style="margin: 5px 0; font-size: 16px; font-weight: bold;">#{{ $order->order_number }}</p>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%" valign="top">
                <span style="color: #777; font-size: 12px; font-weight: bold;">DITAGIHKAN KEPADA:</span><br>
                <strong style="font-size: 16px;">{{ $order->customer->name ?? 'Pelanggan Umum' }}</strong><br>
                {{ $order->customer->address ?? 'Alamat Umum' }}<br>
                Telp: {{ $order->customer->phone ?? '-' }}
            </td>
            <td width="50%" valign="top" class="text-right">
                <span style="color: #777; font-size: 12px; font-weight: bold;">TANGGAL NOTA:</span><br>
                <strong>{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</strong><br><br>
                @if($order->payment_status == 'paid')
                    <div style="color: green; border: 2px solid green; display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; text-transform: uppercase;">LUNAS</div>
                @else
                    <div style="color: red; border: 2px solid red; display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; text-transform: uppercase;">BELUM LUNAS</div>
                @endif
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
                <th width="20%" class="text-right">Harga</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $item->product->name }}</strong></td>
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

    <div class="total-section">
        <table width="50%" align="right">
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
                <td class="text-right total-amount" style="border-top: 2px solid {{ $color }}; padding-top: 10px;">
                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="width: 100%; margin-top: 180px; clear: both;">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                        <strong>Catatan:</strong> {{ $order->notes ?? '-' }}
                    </div>
                    @if(isset($accounts) && count($accounts) > 0)
                        <div style="font-size: 12px; color: #666;">
                            <strong style="color: {{ $color }};">Informasi Pembayaran:</strong><br>
                            @foreach($accounts as $acc)
                                {{ $acc->name }} - <strong>{{ $acc->account_number ?? '-' }}</strong><br>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td width="40%" align="center" style="font-size: 13px;">
                    <p style="margin-bottom: 40px;">Hormat Kami,</p>
                    @if($order->business->signature)
                        <img src="{{ (isset($is_pdf) && $is_pdf) ? public_path('storage/' . $order->business->signature) : asset('storage/' . $order->business->signature) }}" style="height: 60px; display: block; margin: 10px auto;">
                    @endif
                    <p style="font-weight: bold; text-decoration: underline; margin: 0;">{{ $order->business->signer_name ?? 'Otoritas Toko' }}</p>
                    <p style="font-size: 11px; color: #666; margin: 0;">{{ $order->business->signer_title ?? 'Kasir Utama' }}</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>