<?php

namespace Taha20\IranPayment\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Taha20\IranPayment\Contracts\TransactionStore;
use Taha20\IranPayment\Exceptions\IranPaymentException;
use Taha20\IranPayment\Exceptions\InvalidPaymentException;
use Taha20\IranPayment\PaymentManager;
use Taha20\IranPayment\PaymentProcessor;
use Taha20\IranPayment\Support\Amount;
use Taha20\IranPayment\Support\Payment;
use Taha20\IranPayment\Support\PaymentResponse;
use Taha20\IranPayment\Support\PaymentToken;

/**
 * کنترلر آمادهٔ پکیج.
 *
 * اگر به رفتار اختصاصی نیاز دارید (مثلاً ثبت سفارش در همان لحظه)، کافی است
 * در config روت‌های پکیج را خاموش کنید و از PaymentProcessor در کنترلر
 * خودتان استفاده کنید؛ منطق و رویدادها یکی است.
 */
class PaymentController
{
    public function __construct(
        protected PaymentProcessor $processor,
        protected PaymentManager $manager,
        protected TransactionStore $store,
    ) {}

    /**
     * دریافت فرم کامپوننت و هدایت کاربر به درگاه.
     */
    public function pay(Request $request): Response
    {
        try {
            $gateway = (string) $request->input('gateway', '');
            $payment = $this->makePayment($request);

            $response = $this->processor->start($payment, $gateway ?: null);
        } catch (IranPaymentException $e) {
            report($e);

            if (config('app.debug')) {
                throw $e;
            }

            return $this->failureRedirect($e->userMessage());
        }

        return $this->respond($response);
    }

    /**
     * بازگشت کاربر از درگاه.
     */
    public function callback(Request $request, string $gateway, ?string $uuid = null): RedirectResponse
    {
        try {
            $transaction = $this->processor->handleCallback($gateway, $request->all(), $uuid);
        } catch (IranPaymentException $e) {
            report($e);

            if (config('app.debug')) {
                throw $e;
            }

            return $this->failureRedirect($e->userMessage());
        }

        return redirect()->to($transaction->redirectUrl());
    }

    /**
     * صفحهٔ نتیجهٔ داخلی (وقتی پروژه آدرس بازگشت اختصاصی تعیین نکرده باشد).
     */
    public function result(string $transaction): View
    {
        $model = $this->store->findByUuid($transaction);

        abort_if($model === null, 404);

        return view('iran-payment::result', ['transaction' => $model]);
    }

    /**
     * ساخت شیء Payment از ورودی فرم.
     */
    protected function makePayment(Request $request): Payment
    {
        if (config('iran-payment.security.sign_payments', true)) {
            $payload = PaymentToken::parse((string) $request->input('token'));

            $payment = new Payment(
                (int) ($payload['amount'] ?? 0),
                (string) ($payload['order_id'] ?? ''),
                Amount::RIAL // مبلغ داخل توکن همیشه به ریال است
            );

            $payment = $payment
                ->withDescription($payload['description'] ?? null)
                ->withReturnUrl($payload['return_url'] ?? null)
                ->withMobile($payload['mobile'] ?? null)
                ->withEmail($payload['email'] ?? null)
                ->withMeta((array) ($payload['meta'] ?? []));

            // بازگرداندن ارتباط با مدل دامنه (سفارش، اشتراک، ...)
            if (! empty($payload['payable_type']) && ! empty($payload['payable_id'])) {
                $class = Relation::getMorphedModel((string) $payload['payable_type'])
                    ?? (string) $payload['payable_type'];

                if (class_exists($class)) {
                    $payable = $class::query()->find($payload['payable_id']);

                    if ($payable instanceof Model) {
                        $payment->for($payable);
                    }
                }
            }

            return $payment;
        }

        // حالت بدون امضا: مبلغ از فرم خوانده می‌شود. فقط وقتی از این حالت
        // استفاده کنید که مبلغ را در همان درخواست اعتبارسنجی می‌کنید.
        if (! $request->filled('amount') || ! $request->filled('order_id')) {
            throw InvalidPaymentException::invalidToken();
        }

        return new Payment(
            (int) $request->input('amount'),
            (string) $request->input('order_id'),
            (string) $request->input('unit', Amount::defaultUnit())
        );
    }

    /**
     * تبدیل پاسخ درگاه به پاسخ HTTP.
     */
    protected function respond(PaymentResponse $response): Response
    {
        return match ($response->type) {
            PaymentResponse::FORM => response()->view('iran-payment::redirect', [
                'action' => $response->formAction,
                'data' => $response->formData,
            ]),

            PaymentResponse::HTML => response((string) $response->html),

            default => redirect()->away((string) $response->url),
        };
    }

    protected function failureRedirect(string $message): RedirectResponse
    {
        $target = config('iran-payment.redirect.failed');

        if ($target) {
            return redirect()->to($target)->with('iran_payment_error', $message);
        }

        return back()->with('iran_payment_error', $message);
    }
}
