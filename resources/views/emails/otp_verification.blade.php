<!DOCTYPE html>
<html>

<head>
    <title>Email Verification</title>
</head>

<body>
    <h1>Email Verification</h1>
    <p>Your OTP for email verification is: <strong>{{ $otp }}</strong></p>
    <p>This OTP is valid for 10 minutes.</p>
    <p>Thank you for using {{ config('app.name') }}.</p>
</body>

</html>