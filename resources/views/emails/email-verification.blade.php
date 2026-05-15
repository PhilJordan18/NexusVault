<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify your email - NexusVault</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #1e2937;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #334155;
        }
        .header {
            background: #10b981;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #000000;
            font-weight: 700;
        }
        .content {
            padding: 40px 32px;
            text-align: center;
        }
        .button {
            display: inline-block;
            background: #10b981;
            color: #000000;
            font-weight: 700;
            padding: 16px 40px;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 17px;
            margin: 24px 0;
        }
        .footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #64748b;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>NexusVault</h1>
    </div>

    <div class="content">
        <h2 style="color: #10b981; margin-top: 0; font-size: 22px;">Verify your email address</h2>

        <p style="font-size: 16px; line-height: 1.7; color: #cbd5e1;">
            Hello <strong style="color: #f1f5f9;">{{ $name ?? 'friend' }}</strong>,<br><br>
            Thank you for signing up for <strong>NexusVault</strong>!<br>
            To activate your account and use MFA + Passkeys, please verify your email.
        </p>

        <a href="{{ $url }}" class="button">Verify my email</a>

        <p style="color: #64748b; font-size: 14px; margin-top: 32px;">
            This link expires in <strong>60 minutes</strong>.<br>
            If you did not create this account, you can safely ignore this email.
        </p>
    </div>

    <div class="footer">
        The NexusVault Team • Security • Privacy • Simplicity
    </div>
</div>
</body>
</html>
