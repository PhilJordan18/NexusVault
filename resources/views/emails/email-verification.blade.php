<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify your email - NexusVault</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #111111;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #10b98130;
        }
        .header {
            background: #10b981;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #000000;
            font-weight: 700;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .button {
            display: inline-block;
            background: #10b981;
            color: #000000;
            font-weight: 700;
            padding: 16px 32px;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 18px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666666;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>NexusVault</h1>
    </div>

    <div class="content">
        <h2 style="color: #10b981; margin-top: 0;">Verify your email address</h2>

        <p style="font-size: 17px; line-height: 1.6;">
            Hello <strong>{{ $name ?? 'friend' }}</strong>,<br><br>
            Thank you for signing up for <strong>NexusVault</strong>!<br>
            To activate your account and use MFA + Passkeys, click the button below:
        </p>

        <a href="{{ $url }}" class="button" style="color: #000000;">
            Verify my email
        </a>

        <p style="color: #888888; font-size: 14px; margin-top: 30px;">
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
