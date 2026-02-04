<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f7f6; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #2ecc71; }
        p { color: #666; }
        .btn { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Success!</h1>
        <p>Your {{ $gateway ?? 'payment' }} transaction was successful.</p>
        <p>Your wallet will be credited shortly.</p>
    </div>
</body>
</html>
