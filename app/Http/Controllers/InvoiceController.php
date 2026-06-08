<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wallet;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    private function getInvoiceData($orderNumber)
    {
        $order = Order::with(['business', 'customer', 'orderItems.product', 'orderItems.productUnit'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $business = $order->business;

        $templateName = $business->invoice_template ?: 'modern';
        $color = $business->invoice_color ?: '#2563eb';
        $logo = $business->logo;
        $show_discount = $order->discount_amount > 0;

        $accounts = Wallet::where('business_id', $business->id)
            ->where('type', 'bank')
            ->where('is_active', true)
            ->get();

        return compact('order', 'templateName', 'color', 'logo', 'show_discount', 'accounts');
    }

    public function show($orderNumber)
    {
        return view("public.invoices." . $this->getInvoiceData($orderNumber)['templateName'], $this->getInvoiceData($orderNumber));
    }
    public function print($orderNumber)
    {
        return view("public.invoices." . $this->getInvoiceData($orderNumber)['templateName'], $this->getInvoiceData($orderNumber));
    }

    public function download($orderNumber)
    {
        $data = $this->getInvoiceData($orderNumber);
        
        // Tambahkan variabel is_pdf ke dalam data
        $data['is_pdf'] = true; 
        
        $pdf = Pdf::loadView("public.invoices.{$data['templateName']}", $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download("INVOICE-{$orderNumber}.pdf");
    }
    public function previewBatch($orderNumber)
    {
        $data = $this->getInvoiceData($orderNumber);
        // Kita arahkan ke view yang SAMA dengan printBatch, 
        // TAPI kita kirim variabel penanda biar script window.print() tidak jalan.
        $data['is_preview'] = true; 
        return view("public.invoices.batch-dotmatrix", $data);
    }
    public function printBatch($orderNumber)
    {
        // Menggunakan helper data yang sudah kita buat sebelumnya
        $data = $this->getInvoiceData($orderNumber);
        return view("public.invoices.batch-dotmatrix", $data);
    }
}