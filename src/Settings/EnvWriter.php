<?php

namespace Taha20\IranPayment\Settings;

use RuntimeException;

/**
 * نوشتن امن در فایل .env.
 *
 * چرا این کلاس جدا است؟ چون دست‌کاری .env پر از تله است: کوتیشن‌ها،
 * فاصله، کاراکتر #، مسیرهای ویندوزی و نوشتن نیمه‌کاره. اینجا همه‌اش
 * مدیریت می‌شود و نوشتن به‌صورت اتمیک انجام می‌گیرد تا اگر وسط کار
 * خطایی رخ داد، فایل .env نیمه‌نوشته نشود.
 */
class EnvWriter
{
    public function __construct(protected string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $values = [];

        foreach (preg_split('/\r\n|\r|\n/', $this->read()) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if ($key !== '') {
                $values[$key] = $this->unquote(trim($value));
            }
        }

        return $values;
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * تنظیم چند کلید. مقدار null یعنی آن خط از فایل حذف شود.
     *
     * @param  array<string, string|null>  $values
     */
    public function set(array $values): void
    {
        $content = $this->read();

        foreach ($values as $key => $value) {
            $content = $this->apply($content, (string) $key, $value);
        }

        $this->write($content);
    }

    protected function apply(string $content, string $key, ?string $value): string
    {
        $pattern = '/^[ \t]*'.preg_quote($key, '/').'[ \t]*=.*$/m';
        $exists = (bool) preg_match($pattern, $content);

        if ($value === null) {
            return $exists
                ? (string) preg_replace($pattern, '', $content, 1)
                : $content;
        }

        $line = $key.'='.$this->format($value);

        if ($exists) {
            // callback استفاده می‌کنیم تا کاراکترهای $ در مقدار، backreference نشوند
            return (string) preg_replace_callback($pattern, static fn () => $line, $content, 1);
        }

        return rtrim($content, "\r\n").PHP_EOL.$line.PHP_EOL;
    }

    /**
     * قالب‌بندی مقدار برای .env.
     */
    protected function format(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        // مسیرهای ویندوزی و مقادیر دارای بک‌اسلش: نقل‌قول تک، تا escapes تفسیر نشوند
        if (str_contains($value, '\\') && ! str_contains($value, "'")) {
            return "'".$value."'";
        }

        if (preg_match('/[\s#"\']/', $value)) {
            $escaped = str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', '\\n'], $value);

            return '"'.$escaped.'"';
        }

        return $value;
    }

    protected function unquote(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2 && $value[0] === '"' && $value[$length - 1] === '"') {
            $inner = substr($value, 1, -1);

            return str_replace(['\\"', '\\n', '\\\\'], ['"', "\n", '\\'], $inner);
        }

        if ($length >= 2 && $value[0] === "'" && $value[$length - 1] === "'") {
            return substr($value, 1, -1);
        }

        return $value;
    }

    protected function read(): string
    {
        if (! is_file($this->path)) {
            return '';
        }

        $content = file_get_contents($this->path);

        return $content === false ? '' : $content;
    }

    protected function write(string $content): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            throw new RuntimeException(sprintf('پوشهٔ [%s] برای نوشتن فایل .env پیدا نشد.', $directory));
        }

        $temporary = tempnam($directory, '.env.tmp.');

        if ($temporary === false) {
            throw new RuntimeException('ساخت فایل موقت برای نوشتن .env ممکن نشد.');
        }

        if (file_put_contents($temporary, $content) === false) {
            @unlink($temporary);

            throw new RuntimeException('نوشتن فایل موقت .env ممکن نشد.');
        }

        // حفظ دسترسی‌های فایل اصلی (مثلاً 0600)
        $permissions = is_file($this->path) ? (fileperms($this->path) & 0777) : 0644;
        @chmod($temporary, $permissions);

        if (! @rename($temporary, $this->path)) {
            @unlink($temporary);

            throw new RuntimeException(sprintf('جایگزینی فایل [%s] ممکن نشد. دسترسی نوشتن را بررسی کنید.', $this->path));
        }
    }
}
