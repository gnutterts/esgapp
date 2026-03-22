<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inlogcode ESGapp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 24px 32px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .body {
            padding: 32px;
            color: #333333;
            line-height: 1.6;
        }
        .body p {
            margin: 0 0 16px;
        }
        .code {
            display: inline-block;
            margin: 8px 0 24px;
            padding: 14px 32px;
            background-color: #f3f4f6;
            border: 2px solid #1e3a5f;
            border-radius: 8px;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #1e3a5f;
            font-family: monospace;
        }
        .note {
            font-size: 13px;
            color: #777777;
        }
        .footer {
            padding: 16px 32px;
            background-color: #f4f4f4;
            font-size: 12px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>ESGapp</h1>
        </div>
        <div class="body">
            <p>Geachte {{ $user->name }},</p>
            <p>Gebruik onderstaande code om in te loggen:</p>
            <div class="code">{{ $code }}</div>
            <p class="note">Deze code is 15 minuten geldig.</p>
            <p class="note">Als je geen inlogverzoek hebt gedaan, kun je deze e-mail negeren.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ESGapp &mdash; <a href="https://esgapp.nl" style="color: #999999;">esgapp.nl</a>
        </div>
    </div>
</body>
</html>
