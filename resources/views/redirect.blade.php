<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>در حال انتقال به درگاه پرداخت…</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Vazirmatn, IRANSansX, IRANSans, "Segoe UI", Tahoma, system-ui, sans-serif;
            padding: 24px;
        }

        .box {
            width: 100%;
            max-width: 380px;
            padding: 28px 24px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid #e6e8ee;
            box-shadow: 0 20px 44px -28px rgba(15, 23, 42, .5);
            text-align: center;
        }

        .spinner {
            width: 38px;
            height: 38px;
            margin: 0 auto 16px;
            border: 3px solid #e6e8ee;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        h1 { font-size: 15px; margin: 0 0 6px; }

        p { font-size: 12.5px; color: #64748b; margin: 0 0 18px; }

        button {
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            padding: 11px 22px;
            border: 0;
            border-radius: 11px;
            background: #6366f1;
            color: #fff;
            cursor: pointer;
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0b1220; color: #e8eefc; }
            .box { background: #0f172a; border-color: #24334f; }
            .spinner { border-color: #24334f; border-top-color: #818cf8; }
            p { color: #93a4bf; }
        }

        @media (prefers-reduced-motion: reduce) {
            .spinner { animation-duration: 2.4s; }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner" aria-hidden="true"></div>
        <h1>در حال انتقال به درگاه پرداخت</h1>
        <p>لطفاً چند لحظه صبر کنید؛ به صفحهٔ بانک منتقل می‌شوید.</p>

        <form method="POST" action="{{ $action }}" id="iran-payment-redirect">
            @foreach ($data as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <noscript>
                <button type="submit">ادامه به درگاه</button>
            </noscript>
        </form>
    </div>

    <script>
        document.getElementById('iran-payment-redirect').submit();
    </script>
</body>
</html>
