<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Received</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #111827; margin: 0; }
        .wrap { max-width: 720px; margin: 48px auto; padding: 24px; }
        .card { background: #fff; border-radius: 18px; padding: 32px; box-shadow: 0 12px 32px rgba(15, 23, 42, .08); }
        .badge { display: inline-block; background: #dcfce7; color: #166534; padding: 8px 12px; border-radius: 999px; font-size: 14px; font-weight: 700; }
        h1 { margin: 16px 0 12px; font-size: 32px; }
        p { line-height: 1.6; color: #4b5563; }
        .meta { margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <span class="badge">{{ $error ?? false ? 'Payment Pending Review' : 'Payment Received' }}</span>
            <h1>Thank you</h1>
            <p>
                @if ($error ?? false)
                    We could not finalize the {{ $gateway ?? 'payment' }} response for invoice <strong>{{ $invoice->invoice_number }}</strong>.
                @else
                    Your {{ strtolower($gateway ?? 'payment') }} payment for invoice <strong>{{ $invoice->invoice_number }}</strong> has been submitted successfully.
                @endif
            </p>
            <div class="meta">
                <p><strong>Organization:</strong> {{ $invoice->organization?->name }}</p>
                @if ($error ?? false)
                    <p><strong>Details:</strong> {{ $error }}</p>
                @else
                    <p>The platform will update the invoice automatically as soon as {{ $gateway ?? 'the gateway' }} confirms the payment.</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
