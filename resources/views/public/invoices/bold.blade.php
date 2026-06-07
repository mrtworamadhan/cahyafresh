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
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #27272a;
            margin: 0;
            padding: 0;
        }

        .banner {
            background-color:
                {{ $color }}
            ;
            color: #ffffff;
            padding: 40px 30px;
        }

        .content {
            padding: 30px;
        }

        .header-info {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #f4f4f5;
            padding-bottom: 20px;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .grid-table th {
            background-color: #f4f4f5;
            color: #18181b;
            padding: 14px 12px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .grid-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f4f4f5;
        }

        .text-right {
            text-align: right;
        }

        .font-heavy {
            font-weight: 900;
        }

        .font-bold {
            font-weight: 700;
        }

        .grand-total-row {
            background-color:
                {{ $color }}
            ;
            color: #ffffff;
            font-size: 18px;
            font-weight: 900;
        }

        .grand-total-row td {
            padding: 20px 12px;
        }
    </style>
</head>

<body>

    <div class="banner">
        <table width="100%">
            <tr>
                <td>
                    <h1 style="margin: 0; font-size: 36px; font-weight: 900; text-transform: uppercase;">Invoice</h1>
                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 15px;">Nota resmi: #{{ $order->order_number }}
                    </p>
                </td>
                <td class="text-right" valign="top">
                    @if($logo)
                        <img src="{{ asset('storage/' . $logo) }}"
                            style="height: 50px; margin-bottom: 10px; background: #fff; padding: 5px; border-radius: 4px;">
                    @endif
                    <h2 style="margin: 0; font-size: 22px;">{{ $order->business->name }}</h2>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.8;">{{ $order->business->address }}</p>
                    <p style="margin: 2px 0 0 0; font-size: 13px; opacity: 0.8;">{{ $order->business->phone ?? '-' }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="header-info">
            <tr>
                <td width="50%" valign="top">
                    <span style="color: #a1a1aa; font-weight: 800; font-size: 10px; letter-spacing: 1px;">KLIEN
                        MITRA</span>
                    <h3 style="margin: 8px 0 2px 0; font-size: 18px; color: #18181b;">
                        {{ $order->customer->name ?? 'Pelanggan Umum' }}</h3>
                    <p style="margin: 0; color: #52525b; line-height: 1.5;">{{ $order->customer->address ?? '-' }}</p>
                    <p style="margin: 5px 0 0 0; color: #52525b;">Kontak: <span
                            class="font-bold">{{ $order->customer->phone ?? '-' }}</span></p>
                </td>
                <td width="50%" class="text-right" valign="top">
                    <span style="color: #a1a1aa; font-weight: 800; font-size: 10px; letter-spacing: 1px;">DETAIL
                        TRANSAKSI</span>
                    <p style="margin: 8px 0 0 0;">Tanggal Terbit: <span
                            class="font-bold">{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</span>
                    </p>
                    <p style="margin: 5px 0 0 0;">Status Nota:
                        <span
                            style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 900; color: #fff; background-color: {{ $order->payment_status === 'paid' ? '#16a34a' : '#dc2626' }};">
                            {{ strtoupper($order->payment_status) }}
                        </span>
                    </p>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Barang / Komoditas</th>
                    <th width="15%" class="text-right">Satuan</th>
                    <th width="10%" class="text-right">Qty</th>
                    <th width="20%" class="text-right">Harga</th>
                    <th width="20%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $index => $item)
                    <tr>
                        <td class="font-bold">{{ $index + 1 }}</td>
                        <td>
                            <div class="font-bold" style="font-size: 14px;">{{ $item->product->name }}</div>
                        </td>
                        <td class="text-right">{{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
                        <td class="text-right">{{ $item->qty_billed }}</td>
                        <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right font-heavy">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($item->qty_bonus > 0)
                        <tr style="background-color: #f9fafb;">
                            <td></td>
                            <td style="color: #16a34a; font-size: 12px; font-style: italic;">↳ Bonus: {{ $item->product->name }}
                            </td>
                            <td class="text-right" style="font-size: 11px;">{{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
                            <td class="text-right" style="font-size: 11px;">{{ $item->qty_bonus }}</td>
                            <td class="text-right" style="font-size: 11px;">Rp 0</td>
                            <td class="text-right" style="font-size: 11px;">Gratis</td>
                        </tr>
                    @endif
                @endforeach

                <div class="total-section">
                    <table width="50%" align="right">
                        @if($order->discount_amount > 0)
                            <tr>
                                <td class="text-right" style="padding: 5px; color: #666;">Subtotal:</td>
                                <td class="text-right" style="padding: 5px;">Rp
                                    {{ number_format($order->total_amount + $order->discount_amount - $order->shipping_fee_billed, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right" style="padding: 5px; color: red;">Diskon Global:</td>
                                <td class="text-right" style="padding: 5px; color: red;">- Rp
                                    {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if($order->shipping_fee_billed > 0)
                            <tr>
                                <td class="text-right" style="padding: 5px; color: #666;">Ongkos Kirim:</td>
                                <td class="text-right" style="padding: 5px;">Rp
                                    {{ number_format($order->shipping_fee_billed, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="text-right"
                                style="border-top: 2px solid {{ $color }}; padding-top: 10px; font-weight: bold;">TOTAL
                                TAGIHAN:</td>
                            <td class="text-right total-amount"
                                style="border-top: 2px solid {{ $color }}; padding-top: 10px;">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>

                
            </tbody>
        </table>

        <table width="100%" style="margin-top: 60px;">
            <tr>
                <td width="60%" valign="top">
                    @if(isset($accounts) && count($accounts) > 0)
                        <div style="padding: 15px; background-color: #f4f4f5; border-radius: 8px;">
                            <strong style="color: {{ $color }}; font-size: 11px; text-transform: uppercase;">Informasi
                                Pembayaran:</strong>
                            @foreach($accounts as $acc)
                                <div style="margin-top: 8px; font-size: 12px;">
                                    <div class="font-bold">{{ $acc->name }}</div>
                                    <div style="font-family: monospace; font-size: 13px;">{{ $acc->account_number ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td width="40%" align="center" valign="top">
                    <p style="margin-bottom: 50px;">Hormat Kami,</p>
                    @if($order->business->signature)
                        <img src="{{ asset('storage/' . $order->business->signature) }}"
                            style="height: 60px; display: block; margin: 10px auto;">
                    @endif
                    <p class="font-bold" style="text-decoration: underline; margin: 0;">
                        {{ $order->business->signer_name ?? $order->business->name }}</p>
                    <p style="font-size: 11px; color: #71717a; margin: 0;">
                        {{ $order->business->signer_title ?? 'Manajemen' }}</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>