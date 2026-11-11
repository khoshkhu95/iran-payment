<?php

namespace Taha20\IranPayment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Taha20\IranPayment\Settings\EnvWriter;

class EnvWriterTest extends TestCase
{
    protected string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'ipst-env-');
        file_put_contents($this->path, "APP_NAME=Test\nZARINPAL_MERCHANT_ID=old-value\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_it_replaces_existing_values_and_appends_new_ones(): void
    {
        $writer = new EnvWriter($this->path);

        $writer->set([
            'ZARINPAL_MERCHANT_ID' => 'new-value',
            'MELLAT_PASSWORD' => 'p@ss word',
        ]);

        $this->assertSame('new-value', $writer->get('ZARINPAL_MERCHANT_ID'));
        $this->assertSame('p@ss word', $writer->get('MELLAT_PASSWORD'));
        $this->assertSame('Test', $writer->get('APP_NAME'));

        $content = (string) file_get_contents($this->path);

        $this->assertSame(1, substr_count($content, 'ZARINPAL_MERCHANT_ID='));
        $this->assertStringContainsString('MELLAT_PASSWORD="p@ss word"', $content);
    }

    public function test_it_keeps_windows_paths_intact(): void
    {
        $writer = new EnvWriter($this->path);

        $writer->set(['SADAD_PRIVATE_KEY' => 'C:\\keys\\sadad.key']);

        $this->assertSame('C:\\keys\\sadad.key', $writer->get('SADAD_PRIVATE_KEY'));
    }

    public function test_it_removes_a_key_when_value_is_null(): void
    {
        $writer = new EnvWriter($this->path);

        $writer->set(['ZARINPAL_MERCHANT_ID' => null]);

        $this->assertNull($writer->get('ZARINPAL_MERCHANT_ID'));
        $this->assertStringNotContainsString('ZARINPAL_MERCHANT_ID', (string) file_get_contents($this->path));
    }

    public function test_it_does_not_treat_dollar_signs_as_backreferences(): void
    {
        $writer = new EnvWriter($this->path);

        $writer->set(['ZARINPAL_MERCHANT_ID' => 'abc$1def']);

        $this->assertSame('abc$1def', $writer->get('ZARINPAL_MERCHANT_ID'));
    }
}
