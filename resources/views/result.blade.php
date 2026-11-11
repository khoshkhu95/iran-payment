@php
    use Taha20\IranPayment\Support\Persian;

    $status = $transaction->status;
    $color = $status instanceof \Taha20\IranPayment\Enums\TransactionStatus ? $status->color() : '#64748b';
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $transaction->statusLabel() }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Vazirmatn, IRANSansX, IRANSans, "Segoe UI", Tahoma, system-ui, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 420px;
            padding: 26px 22px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid #e6e8ee;
            box-shadow: 0 20px 44px -28px rgba(15, 23, 42, .5);
        }

        .icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: 24px;
            color: {{ $color }};
            background: color-mix(in srgb, {{ $color }} 14%, #fff);
        }

        h1 {
            font-size: 16px;
            text-align: center;
            margin: 0 0 4px;
        }

        .amount {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 18px;
        }

        dl {
            margin: 0;
            padding: 14px 0 0;
            border-top: 1px dashed #e6e8ee;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 9px 14px;
            font-size: 12.5px;
        }

        dt { color: #64748b; }

        dd {
            margin: 0;
            text-align: left;
            font-weight: 600;
            word-break: break-all;
        }

        .back {
            display: block;
            margin-top: 20px;
            padding: 12px;
            border-radius: 11px;
            background: #6366f1;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0b1220; color: #e8eefc; }
            .card { background: #0f172a; border-color: #24334f; }
            dl { border-color: #24334f; }
            dt { color: #93a4bf; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $transaction->isSuccessful() ? '✓' : '!' }}</div>

        <h1>{{ $transaction->statusLabel() }}</h1>
        <p class="amount">{{ $transaction->formattedAmount() }}</p>

        <dl>
            <dt>درگاه</dt>
            <dd>{{ $transaction->gateway }}</dd>

            <dt>شمارهٔ سفارش</dt>
            <dd>{{ Persian::digits($transaction->order_id) }}</dd>

            @if ($transaction->tracking_code)
                <dt>کد رهگیری</dt>
                <dd>{{ Persian::digits($transaction->tracking_code) }}</dd>
            @endif

            @if ($transaction->card_number)
                <dt>کارت</dt>
                <dd>{{ Persian::digits($transaction->card_number) }}</dd>
            @endif

            @if ($transaction->message)
                <dt>توضیح</dt>
                <dd>{{ $transaction->message }}</dd>
            @endif
        </dl>

        <a class="back" href="{{ url('/') }}">بازگشت به فروشگاه</a>
    </div>
</body>
</html>
