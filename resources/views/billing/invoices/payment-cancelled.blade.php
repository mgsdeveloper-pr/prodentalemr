<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Cancelled</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #111827; margin: 0; }
        .wrap { max-width: 720px; margin: 48px auto; padding: 24px; }
        .card { background: #fff; border-radius: 18px; padding: 32px; box-shadow: 0 12px 32px rgba(15, 23, 42, .08); }
        .badge { display: inline-block; background: #fee2e2; color: #991b1b; padding: 8px 12px; border-radius: 999px; font-size: 14px; font-weight: 700; }
        h1 { margin: 16px 0 12px; font-size: 32px; }
        p { line-height: 1.6; color: #4b5563; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <span class="badge">Payment Not Completed</span>
            <h1>Checkout was cancelled</h1>
            <p>No {{ strtolower($gateway ?? 'payment') }} payment was captured for invoice <strong>{{ $invoice->invoice_number }}</strong>.</p>
            <p>You can return to the same payment link later to complete the payment.</p>
        </div>
    </div>
</body>
</html>
