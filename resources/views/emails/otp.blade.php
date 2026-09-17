<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your OTP</title>
</head>
<body style="font-family: sans-serif; background:#f9f9f9; padding: 40px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <h2 style="color:#1a1a1a;">
            @if($purpose === 'verification')
                Verify your FixPay account
            @elseif($purpose === 'login')
                Your FixPay login code
            @else
                Your FixPay OTP
            @endif
        </h2>
        <p style="color:#555;">Use the code below to continue. It expires in {{ $ttlMinutes }} minutes.</p>
        <div style="font-size:36px; font-weight:700; letter-spacing:8px; color:#4f46e5; text-align:center; padding:16px 0;">
            {{ $code }}
        </div>
        <p style="color:#888; font-size:12px;">If you did not request this, you can safely ignore this email.</p>
    </div>
</body>
</html>
