<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to M-DELIGHT Singer Stores</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; color: #1f2937; }
        .wrapper { background: #f3f4f6; padding: 32px 16px; }
        .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #065f46; padding: 28px 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 0.5px; }
        .header span { color: #34d399; }
        .body { padding: 32px; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { font-size: 14px; line-height: 1.7; color: #4b5563; margin: 0 0 16px; }
        .button { display: inline-block; background: #059669; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; margin: 8px 0 24px; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>M-DELIGHT <span>SINGER STORES</span></h1>
            </div>
            <div class="body">
                <h2>Welcome, {{ $user->name }}!</h2>
                <p>Thank you for creating an account with M-DELIGHT Singer Stores. We're excited to have you on board!</p>
                <p>You can now browse our full range of quality products, add them to your cart, and place orders for fast delivery to your doorstep.</p>
                <p style="text-align:center;">
                    <a href="{{ $storeUrl }}" class="button">Start Shopping</a>
                </p>
                <p>If you did not create this account, please ignore this email or contact our support team.</p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} M-DELIGHT Singer Stores. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
