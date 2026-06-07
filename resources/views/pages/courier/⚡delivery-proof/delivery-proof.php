<?php

use App\Models\Delivery;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

new class extends Component
{
    use WithFileUploads;

    public Delivery $delivery;
    
    // State Fase Verifikasi PIN
    public bool $isUnlocked = false;
    public string $pinInput = '';
    
    // State Fase Proof of Delivery (POD)
    public string $receiverName = '';
    public $proofPhoto; // Menampung file foto
    public string $signatureData = ''; // Nanti untuk base64 tanda tangan

    public function mount($tracking_code)
    {
        // Cari data pengiriman berdasarkan resi di URL
        $this->delivery = Delivery::with(['order.customer', 'order.business'])
            ->where('tracking_code', $tracking_code)
            ->firstOrFail();

        // Jika statusnya sudah delivered, langsung buka tanpa PIN agar kurir/konsumen bisa lihat histori
        if ($this->delivery->status === 'delivered') {
            $this->isUnlocked = true;
        }
    }

    public function verifyPin()
    {
        if ($this->pinInput === $this->delivery->access_pin) {
            $this->isUnlocked = true;
            $this->resetErrorBag('pinInput');
        } else {
            $this->addError('pinInput', 'PIN yang Anda masukkan salah. Cek resi Anda.');
            $this->pinInput = ''; 
        }
    }

    public function submitProof()
    {
        $this->validate([
            'receiverName' => 'required|min:3',
            'proofPhoto' => 'required|image|max:5120', 
            'signatureData' => 'required', 
        ], [
            'receiverName.required' => 'Nama penerima wajib diisi.',
            'proofPhoto.required' => 'Foto bukti pengiriman wajib diambil.',
            'proofPhoto.image' => 'File harus berupa gambar.',
            'signatureData.required' => 'Tanda tangan penerima wajib diisi.',
        ]);

        // Simpan foto ke storage publik (folder: proofs)
        $photoPath = $this->proofPhoto->store('proofs', 'public');

        // Update database delivery
        $this->delivery->update([
            'receiver_name' => $this->receiverName,
            'proof_photo_path' => $photoPath,
            'signature_data' => $this->signatureData,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        session()->flash('message', 'Pengiriman Berhasil Diselesaikan!');
    }

    // public function render()
    // {
    //     return view('livewire.courier.delivery-proof')
    //         ->title('Kurir POD - ' . $this->delivery->tracking_code);
    // }
};