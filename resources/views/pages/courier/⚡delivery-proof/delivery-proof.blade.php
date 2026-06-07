<div class="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 flex flex-col items-center p-4 font-sans">
    
    <!-- HEADER -->
    <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 mb-4 text-center">
        <h1 class="text-lg font-black text-blue-600 dark:text-blue-400">SMART DELIVERY</h1>
        <p class="text-xs font-bold text-zinc-500 tracking-widest mt-1">{{ $delivery->tracking_code }}</p>
    </div>

    <!-- FASE 1: LAYAR TERKUNCI (VERIFIKASI PIN) -->
    @if(!$isUnlocked)
        <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 text-center animate-fade-in-up">
            <x-heroicon-o-lock-closed class="w-16 h-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" />
            
            <h2 class="text-xl font-black mb-2">Akses Terkunci</h2>
            <p class="text-sm text-zinc-500 mb-6">Masukkan 4 Digit PIN yang tertera pada resi / surat jalan Anda.</p>

            <form wire:submit.prevent="verifyPin" class="flex flex-col gap-4">
                <div>
                    <!-- Input PIN khusus angka -->
                    <input type="number" pattern="[0-9]*" inputmode="numeric" wire:model="pinInput" placeholder="0000" maxlength="4" class="w-32 mx-auto text-center text-3xl font-black tracking-[0.2em] bg-zinc-50 dark:bg-zinc-900 border-2 border-zinc-300 dark:border-zinc-600 rounded-xl py-3 outline-none focus:border-blue-500 transition shadow-inner">
                    
                    @error('pinInput') 
                        <span class="text-xs text-red-500 font-bold block mt-2">{{ $message }}</span> 
                    @enderror
                </div>
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg shadow-blue-500/30 transition text-lg mt-2">
                    Buka Gembok
                </button>
            </form>
        </div>

    <!-- FASE 2: LAYAR POD (PROOF OF DELIVERY) -->
    @else
        <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-0 overflow-hidden animate-fade-in-up">
            
            <!-- Info Tujuan -->
            <div class="p-5 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/50">
                <span class="text-[10px] font-black uppercase text-blue-500 tracking-wider">Tujuan Pengiriman</span>
                <h2 class="text-lg font-black text-blue-900 dark:text-blue-100 mt-1">{{ $delivery->order->customer->name ?? 'Pelanggan Umum' }}</h2>
                <p class="text-sm font-medium text-blue-800/70 dark:text-blue-200/70 mt-1 leading-relaxed">{{ $delivery->order->customer->address ?? 'Alamat tidak tersedia' }}</p>
                
                <a href="tel:{{ $delivery->order->customer->phone }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-800 text-blue-600 dark:text-blue-400 font-bold text-xs rounded-lg shadow-sm border border-blue-200 dark:border-blue-700 hover:bg-blue-100 transition">
                    <x-heroicon-o-phone class="w-4 h-4" />
                    Hubungi Penerima
                </a>
            </div>

            <!-- Form Serah Terima -->
            @if($delivery->status !== 'delivered')
                <form wire:submit.prevent="submitProof" class="p-5 flex flex-col gap-5">
                    
                    @if (session()->has('message'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl text-sm font-bold text-center">
                            {{ session('message') }}
                        </div>
                    @endif

                    <div>
                        <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Siapa yang menerima paket?</label>
                        <input type="text" wire:model="receiverName" placeholder="Contoh: Pak Budi / Istri / Satpam" class="w-full px-4 py-3 text-base font-bold bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                        @error('receiverName') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 block">Ambil Foto Bukti</label>
                        
                        <!-- Input file dengan atribut 'capture="environment"' akan langsung membuka kamera belakang HP -->
                        <div class="relative w-full h-40 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-900 flex flex-col items-center justify-center overflow-hidden cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                            
                            @if ($proofPhoto)
                                <img src="{{ $proofPhoto->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <x-heroicon-o-camera class="w-10 h-10 text-zinc-400 mb-2" />
                                <span class="text-sm font-bold text-zinc-500">Ketuk untuk Kamera</span>
                            @endif
                            
                            <input type="file" wire:model="proofPhoto" accept="image/*" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        </div>
                        
                        <div wire:loading wire:target="proofPhoto" class="text-xs text-blue-500 font-bold mt-2 text-center w-full">Mengunggah foto...</div>
                        @error('proofPhoto') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div x-data="signaturePad()" class="w-full">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider block">Tanda Tangan Penerima</label>
                            <button type="button" @click="clear()" class="text-[10px] font-bold text-red-500 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-md border border-red-200 dark:border-red-800">Ulangi TTD</button>
                        </div>
                        
                        <div class="w-full h-40 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-900 overflow-hidden relative">
                            <canvas x-ref="canvas" class="w-full h-full touch-none cursor-crosshair"></canvas>
                        </div>
                        
                        @error('signatureData') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="w-full bg-green-500 hover:bg-green-600 text-white font-black py-4 rounded-xl shadow-lg shadow-green-500/30 transition text-lg mt-2 flex justify-center items-center gap-2">
                        <x-heroicon-o-check-circle class="w-6 h-6" />
                        Selesaikan Pengiriman
                    </button>
                </form>

            <!-- Histori Jika Sudah Selesai -->
            @else
                <div class="p-8 text-center bg-green-50 dark:bg-green-900/10">
                    <x-heroicon-s-check-circle class="w-20 h-20 mx-auto text-green-500 mb-4" />
                    <h2 class="text-2xl font-black text-green-700 dark:text-green-400">Pengiriman Selesai!</h2>
                    <p class="text-sm text-green-600 dark:text-green-500 font-medium mt-2">Diterima oleh: <span class="font-bold">{{ $delivery->receiver_name }}</span></p>
                    <p class="text-xs text-green-600/70 dark:text-green-500/70 mt-1">Pada: {{ $delivery->delivered_at->format('d M Y H:i') }}</p>
                </div>
            @endif

        </div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('signaturePad', () => ({
            signatureData: @entangle('signatureData'),
            canvas: null,
            ctx: null,
            isDrawing: false,
            
            init() {
                this.canvas = this.$refs.canvas;
                this.ctx = this.canvas.getContext('2d');
                
                // Set ukuran resolusi canvas agar sesuai dengan ukuran kotak aslinya
                this.resizeCanvas();
                window.addEventListener('resize', () => this.resizeCanvas());

                // Fungsi menangkap posisi jari/mouse
                const getPos = (e) => {
                    const rect = this.canvas.getBoundingClientRect();
                    const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                    const clientY = e.clientY || (e.touches && e.touches[0].clientY);
                    return {
                        x: clientX - rect.left,
                        y: clientY - rect.top
                    };
                };

                // Mulai TTD
                const start = (e) => {
                    e.preventDefault(); // Cegah scroll layar
                    this.isDrawing = true;
                    const pos = getPos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                };

                // Sedang TTD
                const draw = (e) => {
                    if (!this.isDrawing) return;
                    e.preventDefault();
                    const pos = getPos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                };

                // Selesai TTD
                const stop = (e) => {
                    if (!this.isDrawing) return;
                    e.preventDefault();
                    this.isDrawing = false;
                    this.save();
                };

                // Pasang Event Listener Mouse (PC/Laptop)
                this.canvas.addEventListener('mousedown', start);
                this.canvas.addEventListener('mousemove', draw);
                this.canvas.addEventListener('mouseup', stop);
                this.canvas.addEventListener('mouseout', stop);

                // Pasang Event Listener Touch (Smartphone) - passive: false wajib agar e.preventDefault() jalan
                this.canvas.addEventListener('touchstart', start, { passive: false });
                this.canvas.addEventListener('touchmove', draw, { passive: false });
                this.canvas.addEventListener('touchend', stop);
            },
            
            resizeCanvas() {
                const rect = this.canvas.parentElement.getBoundingClientRect();
                this.canvas.width = rect.width;
                this.canvas.height = rect.height;
                
                // Style Tinta Pulpen
                this.ctx.lineWidth = 3;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.strokeStyle = '#2563eb'; // Warna biru seperti pulpen asli
            },
            
            clear() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.signatureData = '';
            },
            
            save() {
                // Simpan coretan ke dalam variabel Livewire $signatureData berbentuk Base64 PNG
                this.signatureData = this.canvas.toDataURL('image/png');
            }
        }))
    })
</script>