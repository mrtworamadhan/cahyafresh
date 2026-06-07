<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak SJ & Invoice</title>
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
</head>
<body>

    <!-- ==========================================
         HALAMAN 1 : INVOICE 
         ========================================== -->
    <div class="text-center">
        <div style="font-size: 16pt; font-weight: bold;">{{ strtoupper($order->business->name) }}</div>
        <div>{{ $order->business->address }} | Telp: {{ $order->business->phone ?? '-' }}</div>
    </div>
    
    <div style="margin-top: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px;">
        <table style="margin-top: 0;">
            <tr>
                <td width="50%">
                    <strong>NOTA : #{{ $order->order_number }}</strong><br>
                    TGL  : {{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y H:i') }}
                </td>
                <td width="50%" class="text-right">
                    <strong>KLIEN:</strong> {{ $order->customer->name ?? 'Umum' }}<br>
                    STATUS: {{ $order->payment_status === 'paid' ? 'LUNAS' : 'PIUTANG' }}
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
    </table>
    <div style="clear: both;"></div>

    <!-- Area Pembayaran dan Tanda Tangan -->
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <!-- Kolom Kiri: Rekening & Catatan -->
            <td width="55%" style="vertical-align: top; font-size: 9pt;">
                @if(isset($accounts) && count($accounts) > 0)
                    <strong>INFO PEMBAYARAN:</strong><br>
                    @foreach($accounts as $acc)
                        - {{ $acc->name }}: {{ $acc->account_number }}<br>
                    @endforeach
                @endif
                <div style="margin-top: 5px;">
                    <strong>CATATAN:</strong> {{ $order->notes ?? '-' }}
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
                                <img src="{{ public_path('storage/' . $order->business->signature) }}" 
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

    <!-- ==========================================
         HALAMAN 2 : SURAT JALAN (Pindah Halaman)
         ========================================== -->
    <div class="page-break"></div>

    <div class="text-center">
        <div style="font-size: 16pt; font-weight: bold;">{{ strtoupper($order->business->name) }}</div>
        <div>{{ $order->business->address }} | Telp: {{ $order->business->phone ?? '-' }}</div>
    </div>
    
    <div class="text-center" style="margin: 10px 0;">
        <span class="header-title">SURAT JALAN</span><br>
        <span>NO: SJ-{{ $order->order_number }}</span>
    </div>

    <table>
        <tr>
            <td width="50%" style="border: 1px solid #000; padding: 5px;">
                <strong>PENGIRIM:</strong><br>{{ $order->business->name }}
            </td>
            <td width="50%" style="border: 1px solid #000; padding: 5px;">
                <strong>PENERIMA:</strong><br>{{ $order->customer->name }}<br>{{ $order->customer->address }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr class="border-y bold">
                <td width="5%" class="text-center">NO</td>
                <td>NAMA BARANG</td>
                <td width="15%" class="text-center">QTY</td>
                <td width="15%" class="text-center">SATUAN</td>
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
                <td>↳ Bonus: {{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty_bonus }}</td>
                <td class="text-center">{{ $item->productUnit?->unit_name ?? 'Pcs' }}</td>
            </tr>
            @endif
            @endforeach
            <tr class="border-y">
                <td colspan="4"></td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 15px;">
        <tr class="text-center">
            <td width="33%">Penerima,</td>
            <td width="33%">Kurir,</td>
            <td width="33%">Hormat Kami,</td>
        </tr>
        <tr>
            <td style="height: 40px;"></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="text-center">
            <td>(____________)</td>
            <td>(____________)</td>
            <td>(____________)</td>
        </tr>
    </table>

    <script>
        @if(!isset($is_preview))
            window.onload = function() { window.print(); }
        @endif
    </script>
</body>
</html>