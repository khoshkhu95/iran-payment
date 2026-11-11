@once
    <style>
        .ipst,
        .ipst * {
            box-sizing: border-box;
        }

        .ipst {
            --ipst-radius: 16px;
            --ipst-bg: #ffffff;
            --ipst-soft: #f8fafc;
            --ipst-soft-2: #f1f5f9;
            --ipst-border: #e5e8ef;
            --ipst-text: #0f172a;
            --ipst-muted: #64748b;
            --ipst-brand: #6366f1;
            --ipst-ok: #10b981;
            --ipst-warn: #f59e0b;
            --ipst-danger: #ef4444;
            --ipst-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 18px 40px -28px rgba(15, 23, 42, .35);
            max-width: 1040px;
            margin-inline: auto;
            border: 1px solid var(--ipst-border);
            border-radius: var(--ipst-radius);
            background: var(--ipst-bg);
            box-shadow: var(--ipst-shadow);
            color: var(--ipst-text);
            font-family: Vazirmatn, IRANSansX, IRANSans, "Segoe UI", Tahoma, system-ui, -apple-system, sans-serif;
            font-size: 13.5px;
            direction: rtl;
            line-height: 1.75;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .ipst[data-theme="dark"] {
            --ipst-bg: #0f172a;
            --ipst-soft: #16233c;
            --ipst-soft-2: #1c2b45;
            --ipst-border: #24334f;
            --ipst-text: #e8eefc;
            --ipst-muted: #93a4bf;
            --ipst-shadow: 0 1px 2px rgba(0, 0, 0, .5), 0 20px 44px -26px rgba(0, 0, 0, .9);
        }

        @media (prefers-color-scheme: dark) {
            .ipst[data-theme="auto"] {
                --ipst-bg: #0f172a;
                --ipst-soft: #16233c;
                --ipst-soft-2: #1c2b45;
                --ipst-border: #24334f;
                --ipst-text: #e8eefc;
                --ipst-muted: #93a4bf;
                --ipst-shadow: 0 1px 2px rgba(0, 0, 0, .5), 0 20px 44px -26px rgba(0, 0, 0, .9);
            }
        }

        /* ---------- سربرگ ---------- */

        .ipst-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 20px 22px 16px;
            border-bottom: 1px solid var(--ipst-border);
        }

        .ipst-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -.2px;
        }

        .ipst-sub {
            margin: 4px 0 0;
            font-size: 12px;
            color: var(--ipst-muted);
        }

        .ipst-chip {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border-radius: 999px;
            background: var(--ipst-soft-2);
            color: var(--ipst-muted);
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .ipst-chip b {
            color: var(--ipst-text);
            font-weight: 700;
        }

        /* ---------- هشدارها ---------- */

        .ipst-alert {
            display: flex;
            gap: 9px;
            margin: 14px 22px 0;
            padding: 11px 13px;
            border-radius: 11px;
            font-size: 12.5px;
            line-height: 1.7;
        }

        .ipst-alert svg {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            margin-top: 2px;
        }

        .ipst-alert-ok {
            background: color-mix(in srgb, var(--ipst-ok) 11%, var(--ipst-bg));
            border: 1px solid color-mix(in srgb, var(--ipst-ok) 34%, var(--ipst-border));
            color: color-mix(in srgb, var(--ipst-ok) 78%, var(--ipst-text));
        }

        .ipst-alert-warn {
            background: color-mix(in srgb, var(--ipst-warn) 12%, var(--ipst-bg));
            border: 1px solid color-mix(in srgb, var(--ipst-warn) 38%, var(--ipst-border));
            color: color-mix(in srgb, var(--ipst-warn) 70%, var(--ipst-text));
        }

        .ipst-alert-danger {
            background: color-mix(in srgb, var(--ipst-danger) 10%, var(--ipst-bg));
            border: 1px solid color-mix(in srgb, var(--ipst-danger) 34%, var(--ipst-border));
            color: color-mix(in srgb, var(--ipst-danger) 80%, var(--ipst-text));
        }

        .ipst-alert code {
            padding: 1px 5px;
            border-radius: 5px;
            background: rgba(127, 127, 127, .16);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11.5px;
            direction: ltr;
            display: inline-block;
        }

        /* ---------- انتخاب درگاه پیش‌فرض ---------- */

        .ipst-default {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 22px 0;
            padding: 12px 14px;
            border: 1px solid var(--ipst-border);
            border-radius: 12px;
            background: var(--ipst-soft);
        }

        .ipst-default label {
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .ipst-default select {
            flex: 1 1 auto;
            min-width: 0;
            max-width: 340px;
        }

        /* ---------- چیدمان ---------- */

        .ipst-layout {
            display: grid;
            grid-template-columns: 244px minmax(0, 1fr);
            margin-top: 16px;
            border-top: 1px solid var(--ipst-border);
        }

        .ipst-nav {
            border-inline-end: 1px solid var(--ipst-border);
            background: var(--ipst-soft);
        }

        .ipst-nav-inner {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 10px;
            max-height: 430px;
            overflow-y: auto;
        }

        .ipst-tab {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: transparent;
            color: var(--ipst-text);
            font: inherit;
            font-size: 12.5px;
            text-align: start;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }

        .ipst-tab:hover {
            background: var(--ipst-bg);
        }

        .ipst-tab.is-active {
            background: var(--ipst-bg);
            border-color: var(--ipst-border);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }

        .ipst-tab:focus-visible {
            outline: 2px solid var(--ipst-brand);
            outline-offset: 1px;
        }

        .ipst-dot {
            flex: 0 0 auto;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--c, var(--ipst-brand));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--c, var(--ipst-brand)) 22%, transparent);
        }

        .ipst-tab-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1 1 auto;
        }

        .ipst-tab-title {
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ipst-tab-sub {
            font-size: 10.5px;
            color: var(--ipst-muted);
        }

        .ipst-tab-flag {
            flex: 0 0 auto;
            width: 15px;
            height: 15px;
        }

        .ipst-tab-flag.ok {
            color: var(--ipst-ok);
        }

        .ipst-tab-flag.warn {
            color: var(--ipst-warn);
        }

        /* ---------- پنل ---------- */

        .ipst-panels {
            min-width: 0;
            padding: 18px 20px 22px;
        }

        .ipst-panel {
            display: none;
        }

        .ipst-panel.is-active {
            display: block;
            animation: ipst-in .22s ease both;
        }

        @keyframes ipst-in {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        .ipst-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 14px;
            margin-bottom: 16px;
            border-bottom: 1px dashed var(--ipst-border);
        }

        .ipst-panel-head h3 {
            margin: 0;
            font-size: 14.5px;
            font-weight: 700;
        }

        .ipst-panel-head p {
            margin: 3px 0 0;
            font-size: 11.5px;
            color: var(--ipst-muted);
        }

        .ipst-panel-title {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ipst-badge {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 600;
            background: var(--ipst-soft-2);
            color: var(--ipst-muted);
        }

        .ipst-badge.ok {
            background: color-mix(in srgb, var(--ipst-ok) 16%, var(--ipst-bg));
            color: color-mix(in srgb, var(--ipst-ok) 80%, var(--ipst-text));
        }

        .ipst-badge.warn {
            background: color-mix(in srgb, var(--ipst-warn) 18%, var(--ipst-bg));
            color: color-mix(in srgb, var(--ipst-warn) 72%, var(--ipst-text));
        }

        /* ---------- سوییچ ---------- */

        .ipst-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            cursor: pointer;
            user-select: none;
        }

        .ipst-switch input[type="checkbox"] {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .ipst-track {
            position: relative;
            display: block;
            width: 42px;
            height: 23px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--ipst-muted) 42%, transparent);
            transition: background .18s ease;
        }

        .ipst-track::after {
            content: "";
            position: absolute;
            top: 3px;
            inset-inline-start: 3px;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .35);
            transition: inset-inline-start .18s ease;
        }

        .ipst-switch input:checked + .ipst-track {
            background: var(--ipst-ok);
        }

        .ipst-switch input:checked + .ipst-track::after {
            inset-inline-start: 22px;
        }

        .ipst-switch input:focus-visible + .ipst-track {
            outline: 2px solid var(--ipst-brand);
            outline-offset: 2px;
        }

        .ipst-switch-label {
            font-size: 11.5px;
            color: var(--ipst-muted);
        }

        /* ---------- فیلدها ---------- */

        .ipst-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .ipst-field {
            min-width: 0;
        }

        .ipst-field.is-wide {
            grid-column: 1 / -1;
        }

        .ipst-field > label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .ipst-req {
            color: var(--ipst-danger);
            margin-inline-start: 2px;
        }

        .ipst-control {
            position: relative;
        }

        .ipst input[type="text"],
        .ipst input[type="password"],
        .ipst input[type="url"],
        .ipst input[type="number"],
        .ipst select,
        .ipst textarea {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid var(--ipst-border);
            border-radius: 10px;
            background: var(--ipst-bg);
            color: var(--ipst-text);
            font-family: inherit;
            font-size: 12.5px;
            line-height: 1.6;
            transition: border-color .15s ease, box-shadow .15s ease;
            appearance: none;
        }

        .ipst input[dir="ltr"],
        .ipst textarea[dir="ltr"] {
            text-align: left;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            letter-spacing: .2px;
        }

        .ipst textarea {
            resize: vertical;
            min-height: 74px;
        }

        .ipst select {
            background-image: linear-gradient(45deg, transparent 50%, var(--ipst-muted) 50%),
                linear-gradient(135deg, var(--ipst-muted) 50%, transparent 50%);
            background-position: calc(0% + 14px) calc(50% - 2px), calc(0% + 19px) calc(50% - 2px);
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
            padding-inline-start: 30px;
        }

        .ipst input:focus,
        .ipst select:focus,
        .ipst textarea:focus {
            outline: none;
            border-color: var(--ipst-brand);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ipst-brand) 18%, transparent);
        }

        .ipst input::placeholder,
        .ipst textarea::placeholder {
            color: color-mix(in srgb, var(--ipst-muted) 78%, transparent);
            font-size: 11.5px;
        }

        .ipst input.is-invalid,
        .ipst textarea.is-invalid,
        .ipst select.is-invalid {
            border-color: var(--ipst-danger);
        }

        .ipst-help {
            margin: 4px 0 0;
            font-size: 11px;
            color: var(--ipst-muted);
            line-height: 1.6;
        }

        .ipst-error {
            margin: 4px 0 0;
            font-size: 11px;
            color: var(--ipst-danger);
        }

        .ipst-reveal {
            position: absolute;
            inset-inline-start: 6px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            padding: 0;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: var(--ipst-muted);
            cursor: pointer;
        }

        .ipst-reveal:hover {
            background: var(--ipst-soft-2);
            color: var(--ipst-text);
        }

        .ipst-reveal svg {
            width: 15px;
            height: 15px;
        }

        .ipst-control:has(.ipst-reveal) input {
            padding-inline-start: 34px;
        }

        /* ---------- سوییچ داخل فرم ---------- */

        .ipst-inline-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            grid-column: 1 / -1;
            padding: 10px 12px;
            border: 1px dashed var(--ipst-border);
            border-radius: 11px;
            background: var(--ipst-soft);
        }

        .ipst-inline-row span {
            font-size: 12px;
            color: var(--ipst-muted);
        }

        .ipst-empty {
            padding: 16px;
            border: 1px dashed var(--ipst-border);
            border-radius: 11px;
            background: var(--ipst-soft);
            color: var(--ipst-muted);
            font-size: 12px;
            text-align: center;
        }

        .ipst-missing {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 16px;
            padding: 9px 11px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--ipst-warn) 10%, var(--ipst-bg));
            border: 1px solid color-mix(in srgb, var(--ipst-warn) 30%, var(--ipst-border));
            color: color-mix(in srgb, var(--ipst-warn) 70%, var(--ipst-text));
            font-size: 11.5px;
        }

        .ipst-missing svg {
            flex: 0 0 auto;
            width: 14px;
            height: 14px;
            margin-top: 3px;
        }

        /* ---------- پاورقی ---------- */

        .ipst-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 22px;
            border-top: 1px solid var(--ipst-border);
            background: var(--ipst-soft);
        }

        .ipst-foot-note {
            margin: 0;
            font-size: 11px;
            color: var(--ipst-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ipst-foot-note svg {
            width: 13px;
            height: 13px;
            flex: 0 0 auto;
        }

        .ipst-save {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            border: 0;
            border-radius: 11px;
            background: var(--ipst-brand);
            color: #fff;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: filter .15s ease, transform .1s ease;
            box-shadow: 0 8px 18px -10px color-mix(in srgb, var(--ipst-brand) 85%, transparent);
        }

        .ipst-save:hover {
            filter: brightness(1.07);
        }

        .ipst-save:active {
            transform: translateY(1px);
        }

        .ipst-save:disabled {
            opacity: .65;
            cursor: progress;
        }

        .ipst-save svg {
            width: 16px;
            height: 16px;
        }

        /* ---------- موبایل ---------- */

        @media (max-width: 760px) {
            .ipst-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .ipst-nav {
                border-inline-end: 0;
                border-bottom: 1px solid var(--ipst-border);
            }

            .ipst-nav-inner {
                flex-direction: row;
                gap: 6px;
                overflow-x: auto;
                overflow-y: hidden;
                max-height: none;
                padding: 10px;
            }

            .ipst-tab {
                flex: 0 0 auto;
                width: auto;
            }

            .ipst-tab-sub {
                display: none;
            }

            .ipst-fields {
                grid-template-columns: minmax(0, 1fr);
            }

            .ipst-head,
            .ipst-foot {
                flex-direction: column;
                align-items: stretch;
            }

            .ipst-alert,
            .ipst-default {
                margin-inline: 14px;
            }

            .ipst-panels {
                padding: 16px 14px 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ipst-panel.is-active {
                animation: none;
            }

            .ipst-tab,
            .ipst-track,
            .ipst-track::after,
            .ipst-save {
                transition: none;
            }
        }
    </style>
@endonce

<div class="ipst" dir="rtl" data-theme="{{ $theme }}" data-ipst>
    <form method="POST" action="{{ $actionUrl }}" data-ipst-form>
        @csrf

        <div class="ipst-head">
            <div>
                <h2 class="ipst-title">{{ $title }}</h2>
                <p class="ipst-sub">
                    اطلاعات هر درگاه را وارد کنید. درگاه‌های فعال با کلید سبز مشخص شده‌اند و
                    فقط همان‌ها در صفحهٔ پرداخت به کاربر نشان داده می‌شوند.
                </p>
            </div>

            <span class="ipst-chip">
                <b>{{ \Taha20\IranPayment\Support\Persian::digits((string) count(array_filter($gateways, fn ($g) => $g['enabled']))) }}</b>
                از
                {{ \Taha20\IranPayment\Support\Persian::digits((string) count($gateways)) }}
                درگاه فعال
            </span>
        </div>

        @if (! $storeAvailable)
            <div class="ipst-alert ipst-alert-warn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                    <path d="M10.3 3.9 2.4 17a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>
                </svg>
                <div>
                    محل ذخیرهٔ تنظیمات (<code>{{ $storeDriver }}</code>) آماده نیست، بنابراین تغییرات ذخیره نخواهند شد.
                    مایگریشن پکیج را منتشر و اجرا کنید:
                    <code>php artisan iran-payment:install</code> و سپس <code>php artisan migrate</code>.
                </div>
            </div>
        @endif

        @if (session('iran_payment_settings_saved'))
            <div class="ipst-alert ipst-alert-ok">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
                <div>تنظیمات با موفقیت ذخیره شد.</div>
            </div>
        @endif

        @if (session('iran_payment_settings_error'))
            <div class="ipst-alert ipst-alert-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                </svg>
                <div>{{ session('iran_payment_settings_error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="ipst-alert ipst-alert-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                </svg>
                <div>
                    {{ $errors->count() }} مورد نیاز به اصلاح دارد؛ فیلدهای مشخص‌شده را بررسی کنید.
                </div>
            </div>
        @endif

        @if (count($gateways) === 0)
            <div class="ipst-panels">
                <div class="ipst-empty">هیچ درگاهی در config تعریف نشده است.</div>
            </div>
        @else
            <div class="ipst-default">
                <label for="ipst-default-gateway">درگاه پیش‌فرض</label>
                <select id="ipst-default-gateway" name="default">
                    @foreach ($defaultOptions as $optionName => $optionTitle)
                        <option value="{{ $optionName }}" @selected(old('default', $defaultGateway) === $optionName)>
                            {{ $optionTitle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ipst-layout">
                <nav class="ipst-nav" role="tablist" aria-label="درگاه‌ها">
                    <div class="ipst-nav-inner">
                        @foreach ($gateways as $name => $gateway)
                        <button
                            type="button"
                            class="ipst-tab @if ($loop->first) is-active @endif"
                            role="tab"
                            id="ipst-tab-{{ $name }}"
                            aria-controls="ipst-panel-{{ $name }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            data-ipst-tab="{{ $name }}"
                        >
                            <span class="ipst-dot" style="--c: {{ $gateway['color'] }}"></span>

                            <span class="ipst-tab-text">
                                <span class="ipst-tab-title">{{ $gateway['title'] }}</span>
                                <span class="ipst-tab-sub">{{ $gateway['enabled'] ? 'فعال' : 'غیرفعال' }}</span>
                            </span>

                            @if ($gateway['configured'])
                                <svg class="ipst-tab-flag ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                            @else
                                <svg class="ipst-tab-flag warn" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 8v4"></path>
                                    <path d="M12 16h.01"></path>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </nav>

                <div class="ipst-panels">
                    @foreach ($gateways as $name => $gateway)
                        <section
                            class="ipst-panel @if ($loop->first) is-active @endif"
                            id="ipst-panel-{{ $name }}"
                            role="tabpanel"
                            aria-labelledby="ipst-tab-{{ $name }}"
                            data-ipst-panel="{{ $name }}"
                        >
                            <div class="ipst-panel-head">
                                <div>
                                    <div class="ipst-panel-title">
                                        <h3>{{ $gateway['title'] }}</h3>

                                        @if ($gateway['configured'])
                                            <span class="ipst-badge ok">تنظیم‌شده</span>
                                        @else
                                            <span class="ipst-badge warn">ناقص</span>
                                        @endif
                                    </div>

                                    @if (! empty($gateway['description']))
                                        <p>{{ $gateway['description'] }}</p>
                                    @endif
                                </div>

                                <label class="ipst-switch">
                                    <input type="hidden" name="gateways[{{ $name }}][enabled]" value="0">
                                    <input
                                        type="checkbox"
                                        name="gateways[{{ $name }}][enabled]"
                                        value="1"
                                        @checked(old('gateways.'.$name.'.enabled', $gateway['enabled']))
                                    >
                                    <span class="ipst-track"></span>
                                    <span class="ipst-switch-label">فعال</span>
                                </label>
                            </div>

                            @if (empty($gateway['fields']))
                                <div class="ipst-empty">این درگاه فیلد قابل تنظیمی ندارد.</div>
                            @else
                                <div class="ipst-fields">
                                    @if ($gateway['supports_sandbox'])
                                        <div class="ipst-inline-row">
                                            <span>
                                                حالت تست (Sandbox) — پرداخت واقعی انجام نمی‌شود و پول جابه‌جا نمی‌گردد.
                                            </span>
                                            <label class="ipst-switch">
                                                <input type="hidden" name="gateways[{{ $name }}][sandbox]" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="gateways[{{ $name }}][sandbox]"
                                                    value="1"
                                                    @checked(old('gateways.'.$name.'.sandbox', $gateway['sandbox']))
                                                >
                                                <span class="ipst-track"></span>
                                            </label>
                                        </div>
                                    @endif

                                    @foreach ($gateway['fields'] as $field)
                                        @continue($field['advanced'] && ! $showAdvanced)

                                        <div @class(['ipst-field', 'is-wide' => $field['wide']])>
                                            <label for="{{ $field['id'] }}">
                                                {{ $field['label'] }}
                                                @if ($field['required'])
                                                    <span class="ipst-req" aria-hidden="true">*</span>
                                                @endif
                                            </label>

                                            @if ($field['type'] === 'select')
                                                <select
                                                    id="{{ $field['id'] }}"
                                                    name="{{ $field['input'] }}"
                                                    @class(['is-invalid' => $errors->has($field['key'])])
                                                >
                                                    @foreach ($field['options'] as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) old($field['key'], $field['value']) === (string) $optionValue)>
                                                            {{ $optionLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @elseif ($field['type'] === 'textarea')
                                                <div class="ipst-control">
                                                    <textarea
                                                        id="{{ $field['id'] }}"
                                                        name="{{ $field['input'] }}"
                                                        rows="3"
                                                        dir="ltr"
                                                        spellcheck="false"
                                                        placeholder="{{ $field['placeholder'] }}"
                                                        @class(['is-invalid' => $errors->has($field['key'])])
                                                    >{{ old($field['key'], $field['value']) }}</textarea>
                                                </div>
                                            @else
                                                <div class="ipst-control">
                                                    <input
                                                        type="{{ $field['type'] === 'password' ? 'password' : ($field['type'] === 'url' ? 'url' : 'text') }}"
                                                        id="{{ $field['id'] }}"
                                                        name="{{ $field['input'] }}"
                                                        value="{{ old($field['key'], $field['value']) }}"
                                                        dir="ltr"
                                                        spellcheck="false"
                                                        autocomplete="{{ $field['secret'] ? 'new-password' : 'off' }}"
                                                        placeholder="{{ $field['placeholder'] }}"
                                                        @class(['is-invalid' => $errors->has($field['key'])])
                                                    >

                                                    @if ($field['secret'])
                                                        <button
                                                            type="button"
                                                            class="ipst-reveal"
                                                            data-ipst-reveal
                                                            aria-label="نمایش مقدار"
                                                            tabindex="-1"
                                                        >
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($field['help'])
                                                <p class="ipst-help">{{ $field['help'] }}</p>
                                            @endif

                                            @error($field['key'])
                                                <p class="ipst-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (! $gateway['configured'])
                                <div class="ipst-missing">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                                        <circle cx="12" cy="12" r="9"></circle>
                                    </svg>
                                    <div>
                                        تا وقتی این موارد پر نشوند، پرداخت با این درگاه خطا می‌دهد:
                                        <strong>{{ implode('، ', $gateway['missing']) }}</strong>
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="ipst-foot">
                <p class="ipst-foot-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <span>
                        مقادیر حساس رمزنگاری‌شده ذخیره می‌شوند و کامل نمایش داده نمی‌شوند.
                        برای تغییر، مقدار جدید وارد کنید؛ خالی گذاشتن یعنی حفظ مقدار فعلی.
                    </span>
                </p>

                <button type="submit" class="ipst-save" data-ipst-submit>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    <span>ذخیرهٔ تنظیمات</span>
                </button>
            </div>
        @endif
    </form>
</div>

@once
    <script>
        (function () {
            if (window.__ipstBound) {
                return;
            }

            window.__ipstBound = true;

            // تب‌ها
            document.addEventListener('click', function (event) {
                var tab = event.target.closest ? event.target.closest('[data-ipst-tab]') : null;

                if (tab) {
                    var root = tab.closest('[data-ipst]');
                    var name = tab.getAttribute('data-ipst-tab');

                    if (!root) {
                        return;
                    }

                    root.querySelectorAll('[data-ipst-tab]').forEach(function (item) {
                        var active = item === tab;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    root.querySelectorAll('[data-ipst-panel]').forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.getAttribute('data-ipst-panel') === name);
                    });

                    return;
                }

                // نمایش/مخفی‌کردن مقدار فیلدهای حساس
                var reveal = event.target.closest ? event.target.closest('[data-ipst-reveal]') : null;

                if (reveal) {
                    var input = reveal.parentNode.querySelector('input');

                    if (input) {
                        input.type = input.type === 'password' ? 'text' : 'password';
                    }
                }
            });

            // جلوگیری از ارسال دوباره
            document.addEventListener('submit', function (event) {
                var form = event.target;

                if (!form || !form.matches || !form.matches('[data-ipst-form]')) {
                    return;
                }

                var button = form.querySelector('[data-ipst-submit]');

                if (button && !button.disabled) {
                    button.disabled = true;
                }
            }, true);
        })();
    </script>
@endonce
