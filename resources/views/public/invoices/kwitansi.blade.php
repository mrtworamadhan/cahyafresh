<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice & Kwitansi</title>
    <style>
        /* 1. PAKSA UKURAN KERTAS & ORIENTASI */
        @page { 
            size: 9.5in 5.5in portrait !important; 
            margin: 0.1in 0.3in !important; 
        }

        /* 2. MATIKAN SEMUA EFEK SMOOTHING */
        body { 
            font-family: "Courier New", Courier, monospace !important; 
            font-size: 10pt !important;
            color: #000000 !important;
            margin: 0;
            -webkit-font-smoothing: none !important;
            text-rendering: optimizeSpeed !important;
            letter-spacing: 1px;
        }

        .page-break { page-break-after: always; }
        
        /* 3. BORDER HITAM PEKAT */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 3px 5px; vertical-align: top; }
        .border-y { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .header-title { font-size: 14pt; font-weight: bold; text-decoration: underline; }
    </style>
    <style>
       
        /* FIX DOMPDF BUG: Hapus width: 100% di sini biar padding nggak tumpah ke kanan */
        .container { border: 3px double {{ $color }}; padding: 15px; margin-top: 20px; }
        
        .header-table { width: 100%; border-bottom: 2px solid {{ $color }}; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 24px; font-weight: bold; color: {{ $color }}; text-transform: uppercase; letter-spacing: 2px; margin: 0; }
        
        .content-table { width: 100%; margin-bottom: 15px; }
        .content-table td { padding: 6px 0; vertical-align: middle; }
        .label-col { width: 22%; font-weight: bold; color: #555; }
        .colon-col { width: 3%; font-weight: bold; }
        .value-col { width: 75%; font-size: 14px; border-bottom: 1px dotted #ccc; }
        
        .terbilang-box { background-color: #f4f4f4; padding: 8px 10px; font-style: italic; font-weight: bold; border-left: 4px solid {{ $color }}; }
        
        .footer-table { width: 100%; margin-top: 10px; }
        .nominal-amount { color: #000000; font-size: 20px; font-weight: bold; padding: 10px 20px; border-radius: 5px; }
    </style>
</head>
<body>
    @php
        $gross_grand_total = 0;
        foreach($order->orderItems as $item) {
            $gross_grand_total += ($item->quantity * $item->unit_price);
        }
        $final_display_total = $show_discount ? $order->total_amount : $gross_grand_total;
    @endphp

    <!-- ==========================================
         HALAMAN 1 : INVOICE 
         ========================================== -->
    <table style="width: 100%; border-collapse: collapse; border-bottom: 2px solid {{ $color ?? '#333' }}; padding-bottom: 12px; margin-bottom: 15px;">
        <tr>
            <td style="width: 30%; text-align: left; vertical-align: middle;">
                @if($order->business && $order->business->logo)
                    <img src="{{ asset('storage/' . $order->business->logo) }}" 
                        alt="Logo {{ $order->business->name }}" 
                        style="max-height: 55px; max-width: 100%; object-fit: contain;">
                @else
                    <div style="font-size: 14pt; font-weight: 900; letter-spacing: 1px; color: #bbb;">
                        {{ substr($order->business->name, 0, 3) }}
                    </div>
                @endif
            </td>
            
            <td style="width: 70%; text-align: right; vertical-align: middle; line-height: 1.4;">
                <div style="font-size: 16pt; font-weight: 900; color: #111; letter-spacing: 0.5px;">
                    {{ strtoupper($order->business->name) }}
                </div>
                <div style="font-size: 9pt; color: #333; margin-top: 3px;">
                    {{ $order->business->address }}
                </div>
                <div style="font-size: 9pt; color: #111; font-weight: bold; margin-top: 1px;">
                    Telp: {{ $order->business->phone ?? '-' }}
                </div>
            </td>
        </tr>
    </table>
    
    <div style="margin-top: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px;">
        <table style="margin-top: 0;">
            <tr>
                <td width="50%">
                    <strong>NOTA : #{{ $order->order_number }}</strong><br>
                    TGL  : {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}
                </td>
                <td width="50%" class="text-right">
                    <strong>KEPADA:</strong> {{ $order->customer->name ?? 'Umum' }}<br>
                    STATUS: '{{ $order->payment_status === 'paid' ? 'LUNAS' : 'PIUTANG' }}'
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr class="border-y bold">
                <td width="5%">NO</td>
                <td>NAMA BARANG</td>
                <td width="15%" class="text-center">QTY</td>
                <td width="20%" class="text-right">HARGA</td>
                <td width="20%" class="text-right">SUBTOTAL</td>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty_billed }} {{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($item->qty_bonus > 0)
            <tr>
                <td></td>
                <td>↳ Bonus: {{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty_bonus }} {{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
                <td class="text-right">0</td>
                <td class="text-right">Gratis</td>
            </tr>
            @endif
            @endforeach
            <tr class="border-y">
                <td colspan="5"></td> <!-- Garis Penutup -->
            </tr>
        </tbody>
    </table>

    <!-- Area Total Harga -->
    <table style="width: 50%; float: right; margin-top: 5px;">
        @if($order->discount_amount > 0)
        <tr>
            <td>DISKON:</td>
            <td class="text-right">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        
        @if($order->shipping_fee_billed > 0)
        <tr>
            <td>ONGKIR:</td>
            <td class="text-right">{{ number_format($order->shipping_fee_billed, 0, ',', '.') }}</td>
        </tr>
        @endif
        
        <tr class="bold">
            <td>TOTAL TAGIHAN:</td>
            <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
        
        <tr>
            <td>TELAH DIBAYAR:</td>
            <td class="text-right">Rp {{ number_format($order->total_amount - $order->remaining_balance, 0, ',', '.') }}</td>
        </tr>
        
        <tr class="bold" style="border-top: 1px solid #000;">
            <td style="padding-top: 5px;">SISA TAGIHAN:</td>
            <td class="text-right" style="padding-top: 5px;">Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}</td>
        </tr>
    </table>
    <div style="clear: both;"></div>

    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; font-size: 9pt;">
                
                <div style="margin-top: 5px;">
                    <strong>CATATAN: Kwitansi Terlampir</strong> {{ $order->notes ?? 'Kwitansi Terlampir' }}
                </div>
            </td>

            <!-- Kolom Kanan: Tanda Tangan -->
            <td width="45%" style="vertical-align: top;">
                <table style="width: 100%; margin-top: 0;">
                    <tr class="text-center">
                        <td width="50%">Hormat Kami,</td>
                    </tr>
                    <tr class="text-center">
                        
                        <td style="vertical-align: bottom; height: 55px;">
                            @if($order->business->signature)
                                <img src="{{ asset('storage/' . $order->business->signature) }}" 
                                    alt="TTD" 
                                    style="height: 40px; object-fit: contain; margin-top: 5px;">
                            @else
                                <div style="height: 40px; margin-top: 5px;"></div>
                            @endif
                            <div style="font-weight: bold; text-decoration: underline;">
                                {{ $order->business->signer_name ?? $order->business->name }}
                            </div>
                            <div style="font-size: 8pt;">
                                {{ $order->business->signer_title ?? 'Manajemen' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>
    @php
        $gross_grand_total = 0;
        foreach($order->orderItems as $item) {
            $gross_grand_total += ($item->quantity * $item->unit_price);
        }
        $final_display_total = $order->total_amount;
    @endphp
    @php

        if (!function_exists('penyebut')) {
            function penyebut($nilai) {
                $nilai = abs($nilai);
                $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
                $temp = "";
                if ($nilai < 12) {
                    $temp = " ". $huruf[$nilai];
                } else if ($nilai < 20) {
                    $temp = penyebut($nilai - 10). " Belas";
                } else if ($nilai < 100) {
                    $temp = penyebut($nilai/10)." Puluh". penyebut($nilai % 10);
                } else if ($nilai < 200) {
                    $temp = " Seratus" . penyebut($nilai - 100);
                } else if ($nilai < 1000) {
                    $temp = penyebut($nilai/100) . " Ratus" . penyebut($nilai % 100);
                } else if ($nilai < 2000) {
                    $temp = " Seribu" . penyebut($nilai - 1000);
                } else if ($nilai < 1000000) {
                    $temp = penyebut($nilai/1000) . " Ribu" . penyebut($nilai % 1000);
                } else if ($nilai < 1000000000) {
                    $temp = penyebut($nilai/1000000) . " Juta" . penyebut($nilai % 1000000);
                }
                return $temp;
            }
        }
        $terbilang = trim(penyebut($final_display_total)) . " Rupiah";
    @endphp

    <div class="container">
        
        <table class="header-table">
            <tr>
                <td width="60%" valign="middle">
                    @if($order->business && $order->business->logo)
                        <img src="{{ asset('storage/' . $order->business->logo) }}" 
                            alt="Logo {{ $order->business->name }}" 
                            style="max-height: 55px; max-width: 100%; object-fit: contain;">
                    @else
                        <div style="font-size: 14pt; font-weight: 900; letter-spacing: 1px; color: #bbb;">
                            {{ substr($order->business->name, 0, 3) }}
                        </div>
                    @endif
                    <div style="font-size: 11px; color: #777;">
                        {{ $order->business->address }} | Telp: {{ $order->business->phone ?? '-' }}
                    </div>
                </td>
                <td width="40%" valign="middle" style="text-align: right;">
                    <h1 class="title">KWITANSI</h1>
                    <div style="font-size: 12px; color: #777; margin-top: 5px;">No: PYI-{{ $order->order_number }}</div>
                </td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="label-col">Telah Terima Dari</td>
                <td class="colon-col">:</td>
                <td class="value-col"><strong>{{ $order->customer->name }}</strong></td>
            </tr>
            <tr>
                <td class="label-col">Uang Sejumlah</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="border-bottom: none; padding-top: 10px; padding-bottom: 10px;">
                    <div class="terbilang-box">
                        ## {{ $terbilang }} ##
                    </div>
                </td>
            </tr>
            <tr>
                <td class="label-col" valign="top" style="padding-top: 5px;">Untuk Pembayaran</td>
                <td class="colon-col" valign="top" style="padding-top: 5px;">:</td>
                <td class="value-col" style="padding-top: 5px;">
                    <strong>Pelunasan Invoice No.{{ $order->order_number }} tgl:{{ \Carbon\Carbon::parse($order->delivery_date)->translatedFormat('d F Y') }} </strong>
                    
                    <div style="font-size: 13px; margin-top: 6px; color: #333;">
                        Rincian Pembelian:
                        <ul style="margin: 4px 0 0 16px; padding: 0;">
                            @foreach($order->orderItems as $item)
                                <li>
                                    {{ $item->product->name ?? 'Produk Tidak Ditemukan' }} 
                                    (Qty: {{ $item->qty_billed }} {{ $item->productUnit?->unit_name ?? 'Pcs' }}) 
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td width="50%" valign="bottom">
                    <span class="nominal-amount">
                        Rp {{ number_format($final_display_total, 0, ',', '.') }}
                    </span>
                </td>
                <td width="50%" valign="bottom" style="text-align: right;">
                    <p style="margin: 0 0 5px 0;">{{ \Carbon\Carbon::parse($order->updated_at)->translatedFormat('d F Y') }}</p>
                    <p style="color: #777; font-size: 12px; margin: 0;">Penerima,</p>
                    
                    
                        <br><br><br>
                    
                    
                    <p style="text-decoration: underline; font-size: 12px; font-weight: bold; margin: 0;">
                        {{ $order->business->signer_title ?? $order->business->name }}
                    </p>
                </td>
            </tr>
        </table>

    </div>

    <script>
        @if(!isset($is_preview))
            window.onload = function() { window.print(); }
        @endif
    </script>
</body>
</html>