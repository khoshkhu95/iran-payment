@once
    <style>
        .ipx,
        .ipx * {
            box-sizing: border-box;
        }

        .ipx {
            --ipx-radius: 18px;
            --ipx-brand: #6366f1;
            --ipx-bg: #ffffff;
            --ipx-soft: #f8fafc;
            --ipx-border: #e6e8ee;
            --ipx-text: #0f172a;
            --ipx-muted: #64748b;
            --ipx-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 18px 40px -24px rgba(15, 23, 42, .35);
            max-width: 580px;
            margin-inline: auto;
            padding: 20px;
            border: 1px solid var(--ipx-border);
            border-radius: var(--ipx-radius);
            background: var(--ipx-bg);
            box-shadow: var(--ipx-shadow);
            color: var(--ipx-text);
            font-family: Vazirmatn, IRANSansX, IRANSans, "Segoe UI", Tahoma, system-ui, -apple-system, sans-serif;
            direction: rtl;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        .ipx[data-theme="dark"] {
            --ipx-bg: #0f172a;
            --ipx-soft: #16233c;
            --ipx-border: #24334f;
            --ipx-text: #e8eefc;
            --ipx-muted: #93a4bf;
            --ipx-shadow: 0 1px 2px rgba(0, 0, 0, .5), 0 20px 44px -26px rgba(0, 0, 0, .9);
        }

        @media (prefers-color-scheme: dark) {
            .ipx[data-theme="auto"] {
                --ipx-bg: #0f172a;
                --ipx-soft: #16233c;
                --ipx-border: #24334f;
                --ipx-text: #e8eefc;
                --ipx-muted: #93a4bf;
                --ipx-shadow: 0 1px 2px rgba(0, 0, 0, .5), 0 20px 44px -26px rgba(0, 0, 0, .9);
            }
        }

        .ipx-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px dashed var(--ipx-border);
        }

        .ipx-head-title {
            display: block;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -.2px;
        }

        .ipx-head-sub {
            display: block;
            font-size: 12px;
            color: var(--ipx-muted);
            margin-top: 2px;
        }

        .ipx-amount {
            flex: 0 0 auto;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--ipx-soft);
            border: 1px solid var(--ipx-border);
            font-size: 15px;
            font-weight: 700;
            white-space: nowrap;
        }

        .ipx-amount small {
            font-size: 11px;
            font-weight: 500;
            color: var(--ipx-muted);
            margin-inline-start: 4px;
        }

        .ipx-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }

        .ipx[data-layout="grid"] .ipx-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }

        .ipx-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border: 1.5px solid var(--ipx-border);
            border-radius: 14px;
            background: var(--ipx-bg);
            cursor: pointer;
            transition: border-color .18s ease, background-color .18s ease, transform .18s ease, box-shadow .18s ease;
            user-select: none;
        }

        .ipx-card:hover {
            border-color: color-mix(in srgb, var(--ipx-brand) 55%, var(--ipx-border));
            transform: translateY(-1px);
        }

        .ipx-card input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: 0;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        .ipx-card:has(input:checked) {
            border-color: var(--ipx-brand);
            background: color-mix(in srgb, var(--ipx-brand) 7%, var(--ipx-bg));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ipx-brand) 18%, transparent);
        }

        .ipx-card:has(input:focus-visible) {
            outline: 2px solid var(--ipx-brand);
            outline-offset: 2px;
        }

        .ipx-badge {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 800;
            background: color-mix(in srgb, var(--ipx-brand) 14%, var(--ipx-bg));
            color: var(--ipx-brand);
            overflow: hidden;
        }

        .ipx-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .ipx-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1 1 auto;
        }

        .ipx-title {
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ipx-desc {
            font-size: 11.5px;
            color: var(--ipx-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ipx-check {
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid var(--ipx-border);
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .ipx-card:has(input:checked) .ipx-check {
            border-color: var(--ipx-brand);
            background: var(--ipx-brand);
            box-shadow: inset 0 0 0 3px var(--ipx-bg);
        }

        .ipx-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 16px;
            padding: 13px 18px;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--ipx-brand), color-mix(in srgb, var(--ipx-brand) 70%, #000));
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: filter .18s ease, transform .12s ease, opacity .18s ease;
        }

        .ipx-submit:hover {
            filter: brightness(1.06);
        }

        .ipx-submit:active {
            transform: translateY(1px);
        }

        .ipx-submit:disabled {
            opacity: .65;
            cursor: progress;
        }

        .ipx-submit svg {
            width: 17px;
            height: 17px;
        }

        .ipx-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 12px 0 0;
            font-size: 11.5px;
            color: var(--ipx-muted);
            text-align: center;
        }

        .ipx-note svg {
            width: 13px;
            height: 13px;
            flex: 0 0 auto;
        }

        .ipx-empty {
            padding: 18px;
            border: 1px dashed var(--ipx-border);
            border-radius: 13px;
            background: var(--ipx-soft);
            color: var(--ipx-muted);
            font-size: 13px;
            text-align: center;
        }

        .ipx-error {
            margin-bottom: 14px;
            padding: 11px 13px;
            border-radius: 11px;
            background: color-mix(in srgb, #ef4444 12%, var(--ipx-bg));
            border: 1px solid color-mix(in srgb, #ef4444 35%, var(--ipx-border));
            color: #ef4444;
            font-size: 12.5px;
        }

        @media (max-width: 420px) {
            .ipx {
                padding: 15px;
                border-radius: 15px;
            }

            .ipx[data-layout="grid"] .ipx-grid {
                grid-template-columns: 1fr;
            }

            .ipx-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ipx-card,
            .ipx-submit,
            .ipx-check {
                transition: none;
            }
        }
    </style>
@endonce

<div class="ipx" dir="rtl" data-theme="{{ $theme }}" data-layout="{{ $layout }}">
    <form method="POST" action="{{ $actionUrl }}" data-ipx-form>
        @csrf

        @if ($token !== '')
            <input type="hidden" name="token" value="{{ $token }}">
        @else
            <input type="hidden" name="amount" value="{{ $amountInRial }}">
            <input type="hidden" name="unit" value="IRR">
            <input type="hidden" name="order_id" value="{{ $orderId }}">
        @endif

        <div class="ipx-head">
            <div>
                <span class="ipx-head-title">درگاه پرداخت را انتخاب کنید</span>
                <span class="ipx-head-sub">شمارهٔ سفارش: {{ \Taha20\IranPayment\Support\Persian::digits($orderId) }}</span>
            </div>

            @if ($showAmount)
                <div class="ipx-amount">
                    {{ \Taha20\IranPayment\Support\Persian::digits(number_format(\Taha20\IranPayment\Support\Amount::toToman($amountInRial))) }}
                    <small>تومان</small>
                </div>
            @endif
        </div>

        @if (session('iran_payment_error'))
            <div class="ipx-error">{{ session('iran_payment_error') }}</div>
        @endif

        @if (empty($gateways))
            <div class="ipx-empty">
                هیچ درگاه پرداختی فعال نیست. لطفاً در تنظیمات پکیج حداقل یک درگاه را فعال کنید.
            </div>
        @else
            <div class="ipx-grid" role="radiogroup" aria-label="انتخاب درگاه پرداخت">
                @foreach ($gateways as $name => $item)
                    <label class="ipx-card" style="--ipx-brand: {{ $item['color'] ?: '#6366f1' }}">
                        <input
                            type="radio"
                            name="gateway"
                            value="{{ $name }}"
                            @checked($name === $selectedGateway)
                            required
                        >

                        <span class="ipx-badge">
                            @if (! empty($item['logo']))
                                <img src="{{ $item['logo'] }}" alt="{{ $item['title'] }}" loading="lazy">
                            @else
                                {{ \Illuminate\Support\Str::substr($item['title'], 0, 1) }}
                            @endif
                        </span>

                        <span class="ipx-body">
                            <span class="ipx-title">{{ $item['title'] }}</span>
                            @if (! empty($item['description']))
                                <span class="ipx-desc">{{ $item['description'] }}</span>
                            @endif
                        </span>

                        <span class="ipx-check" aria-hidden="true"></span>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="ipx-submit" data-ipx-submit>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="5" width="20" height="14" rx="3"></rect>
                    <path d="M2 10h20"></path>
                </svg>
                <span>{{ $buttonLabel }}</span>
            </button>

            <p class="ipx-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                </svg>
                <span>{{ $note ?: 'اطلاعات پرداخت شما رمزنگاری شده و در اختیار درگاه قرار می‌گیرد.' }}</span>
            </p>
        @endif
    </form>
</div>

@once
    <script>
        document.addEventListener('submit', function (event) {
            var form = event.target;

            if (!form || !form.matches || !form.matches('[data-ipx-form]')) {
                return;
            }

            var button = form.querySelector('[data-ipx-submit]');

            if (button && !button.disabled) {
                button.disabled = true;
                button.style.opacity = '.7';
            }
        }, true);
    </script>
@endonce
