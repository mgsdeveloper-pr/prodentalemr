<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\SaasSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdf
{
    public static function fileName(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }

    public static function output(Invoice $invoice): string
    {
        $invoice->loadMissing(['organization.locations', 'items', 'payments', 'subscription.subscriptionPlan']);

        return Pdf::loadView('pdf.invoices.show', [
            'invoice' => $invoice,
            'settings' => SaasSetting::current(),
            'logoPath' => static::logoPath(),
            'signaturePath' => static::signaturePath(),
        ])
            ->setPaper('a4')
            ->output();
    }

    protected static function logoPath(): ?string
    {
        $settings = SaasSetting::current();

        $logo = $settings->invoice_logo_path ?: $settings->logo_path;

        if (blank($logo)) {
            return null;
        }

        $path = public_path('uploads/' . ltrim($logo, '/'));

        return is_file($path) ? $path : null;
    }

    protected static function signaturePath(): ?string
    {
        $settings = SaasSetting::current();

        if (blank($settings->invoice_signature_path)) {
            return null;
        }

        $path = public_path('uploads/' . ltrim($settings->invoice_signature_path, '/'));

        return is_file($path) ? $path : null;
    }
}
