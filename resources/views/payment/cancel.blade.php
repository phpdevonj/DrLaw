<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f7f6; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #e74c3c; }
        p { color: #666; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Cancelled</h1>
        <p>Your {{ $gateway ?? 'payment' }} transaction was cancelled.</p>
    </div>
</body>
</html>
