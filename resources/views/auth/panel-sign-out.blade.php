<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signing Out - Denentiq</title>
    @include('partials.brand-icons')
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .card {
            width: min(28rem, calc(100vw - 2rem));
            padding: 2rem;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .text {
            margin: 0 0 1rem;
            color: #475569;
        }

        .link {
            color: #b45309;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><img src="{{ asset('images/brand/denentiq-horizontal-primary.svg') }}" alt="Denentiq" style="width: 180px; height: 45px; object-fit: contain; margin: auto;"></div>
        <p class="text">Signing you out...</p>
        <p class="text">
            If you are not redirected automatically,
            <button class="link" type="submit" form="panel-sign-out-form">continue here</button>.
        </p>
    </div>

    <form id="panel-sign-out-form" method="POST" action="{{ $performUrl }}" style="display: none;">
        @csrf
    </form>

    <script>
        document.getElementById('panel-sign-out-form').submit();
    </script>
</body>
</html>
