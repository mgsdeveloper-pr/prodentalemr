<?php

use App\Http\Controllers\Billing\InvoicePaymentPageController;
use App\Http\Controllers\Billing\PayPalCheckoutController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Admin\AdminClinicScopeController;
use App\Http\Controllers\Auth\PanelLogoutController;
use App\Http\Controllers\Clinic\BillingWorkItemAttachmentController as ClinicBillingWorkItemAttachmentController;
use App\Http\Controllers\Clinic\PatientFinancialDocumentController;
use App\Http\Controllers\Clinic\ClinicPanelScopeController;
use App\Http\Controllers\Clinic\PatientConsentFormController;
use App\Http\Controllers\Clinic\PatientDocumentController;
use App\Http\Controllers\Clinic\VerificationRequestSampleController;
use App\Http\Controllers\Verification\VerificationNotificationActionController;
use App\Http\Controllers\Verification\VerificationAttentionQueueExportController;
use App\Http\Controllers\Verification\VerificationAuditTrailController;
use App\Http\Controllers\Verification\VerificationResultPdfController;
use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Http\Controllers\Saas\BillingWorkItemAttachmentController;
use App\Http\Controllers\Saas\InvoicePdfController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::redirect('/verification/login', '/login');
Route::redirect('/admin/login', '/login');
Route::redirect('/saas/login', '/login');
Route::redirect('/clinic/login', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/clinic/patient-documents/{document}/view', [PatientDocumentController::class, 'show'])->name('clinic.patient-documents.show');
    Route::get('/clinic/patient-documents/{document}/download', [PatientDocumentController::class, 'download'])->name('clinic.patient-documents.download');
    Route::get('/clinic/patient-consent-forms/{consent}/view', [PatientConsentFormController::class, 'show'])->name('clinic.patient-consent-forms.show');
    Route::get('/clinic/patient-consent-forms/{consent}/download', [PatientConsentFormController::class, 'download'])->name('clinic.patient-consent-forms.download');
    Route::get('/clinic/patient-statements/{statement}/view', [PatientFinancialDocumentController::class, 'showStatement'])->name('clinic.patient-statements.show');
    Route::get('/clinic/patient-statements/{statement}/download', [PatientFinancialDocumentController::class, 'downloadStatement'])->name('clinic.patient-statements.download');
    Route::get('/clinic/patient-ledger-entries/{entry}/receipt/view', [PatientFinancialDocumentController::class, 'showReceipt'])->name('clinic.patient-ledger-entries.receipt.show');
    Route::get('/clinic/patient-ledger-entries/{entry}/receipt/download', [PatientFinancialDocumentController::class, 'downloadReceipt'])->name('clinic.patient-ledger-entries.receipt.download');
    Route::get('/verification/sign-out', fn () => view('auth.panel-sign-out', ['performUrl' => route('admin.signout.perform')]))->name('admin.signout');
    Route::get('/verification/clinic-scope', AdminClinicScopeController::class)->name('admin.clinic-scope');
    Route::get('/clinic/sign-out', fn () => view('auth.panel-sign-out', ['performUrl' => route('clinic.signout.perform')]))->name('clinic.signout');
    Route::get('/clinic/clinic-scope', ClinicPanelScopeController::class)->name('clinic.clinic-scope');
    Route::get('/clinic/verification-requests/sample', VerificationRequestSampleController::class)->name('clinic.verification-requests.sample');
    Route::get('/clinic/verification-requests/{billingWorkItem}/start', function (BillingWorkItem $billingWorkItem) {
        abort_unless(auth()->user()?->canAccessClinicVerificationRequests(), 403);
        abort_unless($billingWorkItem->clinicUserCanEditVerification(auth()->user()), 403);

        $billingWorkItem->startWork(auth()->id());
        $billingWorkItem->recordActivity('clinic_verification_started', 'Clinic user started the verification form.', [
            'panel' => 'clinic',
            'user_name' => auth()->user()?->name,
        ]);

        return redirect()->route('filament.clinic.resources.verification-requests.edit', ['record' => $billingWorkItem]);
    })->name('clinic.verification-requests.start');
    Route::get('/saas/sign-out', fn () => view('auth.panel-sign-out', ['performUrl' => route('saas.signout.perform')]))->name('saas.signout');
    Route::get('/verification/sign-out/perform', PanelLogoutController::class)->name('admin.signout.perform');
    Route::get('/clinic/sign-out/perform', PanelLogoutController::class)->name('clinic.signout.perform');
    Route::get('/saas/sign-out/perform', PanelLogoutController::class)->name('saas.signout.perform');
    Route::get('/verification/verifications/{billingWorkItem}/start', function (BillingWorkItem $billingWorkItem) {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);

        $billingWorkItem->startWork(auth()->id());

        return redirect(VerificationWorkItemResource::getUrl('edit', ['record' => $billingWorkItem]));
    })->name('admin.verifications.start');
    Route::get('/verification/verifications/{billingWorkItem}/download', [VerificationResultPdfController::class, 'downloadForAdmin'])->name('admin.verifications.pdf.download');
    Route::get('/verification/verifications/{billingWorkItem}/preview', [VerificationResultPdfController::class, 'previewForAdmin'])->name('admin.verifications.pdf.preview');
    Route::get('/verification/verifications/{billingWorkItem}/audit', [VerificationAuditTrailController::class, 'downloadForAdmin'])->name('admin.verifications.audit.download');
    Route::get('/verification/attention-queue/export/excel', [VerificationAttentionQueueExportController::class, 'excel'])->name('admin.verification-attention-queue.export.excel');
    Route::get('/verification/attention-queue/export/pdf', [VerificationAttentionQueueExportController::class, 'pdf'])->name('admin.verification-attention-queue.export.pdf');
    Route::get('/verification/notifications/{notification}/open', [VerificationNotificationActionController::class, 'open'])->defaults('panel', 'verification')->name('admin.verification-notifications.open');
    Route::post('/verification/notifications/{notification}/read', [VerificationNotificationActionController::class, 'markRead'])->defaults('panel', 'verification')->name('admin.verification-notifications.read');
    Route::post('/verification/notifications/read-all', [VerificationNotificationActionController::class, 'markAllRead'])->defaults('panel', 'verification')->name('admin.verification-notifications.read-all');
    Route::get('/clinic/verification-requests/{billingWorkItem}/download', [VerificationResultPdfController::class, 'downloadForClinic'])->name('clinic.verification-requests.pdf.download');
    Route::get('/clinic/verification-requests/{billingWorkItem}/preview', [VerificationResultPdfController::class, 'previewForClinic'])->name('clinic.verification-requests.pdf.preview');
    Route::get('/clinic/billing-work-item-attachments/{attachment}/download', [ClinicBillingWorkItemAttachmentController::class, 'download'])->name('clinic.billing-work-item-attachments.download');
    Route::get('/clinic/notifications/{notification}/open', [VerificationNotificationActionController::class, 'open'])->defaults('panel', 'clinic')->name('clinic.verification-notifications.open');
    Route::post('/clinic/notifications/{notification}/read', [VerificationNotificationActionController::class, 'markRead'])->defaults('panel', 'clinic')->name('clinic.verification-notifications.read');
    Route::post('/clinic/notifications/read-all', [VerificationNotificationActionController::class, 'markAllRead'])->defaults('panel', 'clinic')->name('clinic.verification-notifications.read-all');
    Route::get('/saas/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show'])->name('saas.invoices.pdf.view');
    Route::get('/saas/invoices/{invoice}/download', [InvoicePdfController::class, 'download'])->name('saas.invoices.pdf.download');
    Route::get('/saas/billing-work-item-attachments/{attachment}/download', [BillingWorkItemAttachmentController::class, 'download'])->name('saas.billing-work-item-attachments.download');
});

Route::get('/admin/{path?}', function (Request $request, ?string $path = null) {
    $target = '/verification' . ($path ? '/' . ltrim($path, '/') : '');

    if ($request->getQueryString()) {
        $target .= '?' . $request->getQueryString();
    }

    return redirect($target, 301);
})->where('path', '.*');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::post('/paypal/webhook', [PayPalCheckoutController::class, 'webhook'])->name('paypal.webhook');
Route::get('/billing/invoices/{invoice}/pay', [InvoicePaymentPageController::class, 'show'])
    ->middleware('signed')
    ->name('billing.invoices.payment.page');
Route::get('/billing/invoices/{invoice}/pay/paypal', [PayPalCheckoutController::class, 'show'])
    ->middleware('signed')
    ->name('billing.invoices.paypal.page');
Route::get('/billing/invoices/{invoice}/payment/success', [InvoicePaymentPageController::class, 'success'])
    ->middleware('signed')
    ->name('billing.invoices.payment.success');
Route::get('/billing/invoices/{invoice}/payment/cancelled', [InvoicePaymentPageController::class, 'cancel'])
    ->middleware('signed')
    ->name('billing.invoices.payment.cancel');
Route::get('/billing/invoices/{invoice}/paypal/return', [PayPalCheckoutController::class, 'complete'])
    ->name('billing.invoices.paypal.return');
Route::get('/billing/invoices/{invoice}/paypal/cancelled', [PayPalCheckoutController::class, 'cancel'])
    ->name('billing.invoices.paypal.cancel');

require __DIR__.'/auth.php';
