<x-filament-panels::page>
    
    {{-- Merender Filter Tanggal --}}
    {{ $this->form }}

    {{-- Merender Laporan (Laba Rugi, Piutang, Hutang, Neraca) secara Native --}}
    {{ $this->infolist }}

    <x-filament-actions::modals />

</x-filament-panels::page>