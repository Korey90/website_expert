<?php

namespace Tests\Unit\Seo;

use App\Support\Seo\DomainNormalizer;
use Tests\TestCase;

class DomainNormalizerTest extends TestCase
{
    private DomainNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new DomainNormalizer;
    }

    public function test_normalize_strips_protocol_www_path_and_trailing_slash(): void
    {
        $this->assertSame('example.com', $this->normalizer->normalize('example.com'));
        $this->assertSame('example.com', $this->normalizer->normalize('www.example.com'));
        $this->assertSame('example.com', $this->normalizer->normalize('https://example.com'));
        $this->assertSame('example.com', $this->normalizer->normalize('https://www.example.com/'));
        $this->assertSame('example.com', $this->normalizer->normalize('https://www.example.com/services/web-design'));
        $this->assertSame('example.com', $this->normalizer->normalize('HTTPS://WWW.EXAMPLE.COM'));
    }

    public function test_is_valid_domain_rejects_ips_localhost_and_garbage(): void
    {
        $this->assertTrue($this->normalizer->isValidDomain('example.com'));
        $this->assertTrue($this->normalizer->isValidDomain('example.co.uk'));
        $this->assertFalse($this->normalizer->isValidDomain('localhost'));
        $this->assertFalse($this->normalizer->isValidDomain('127.0.0.1'));
        $this->assertFalse($this->normalizer->isValidDomain('192.168.1.1'));
        $this->assertFalse($this->normalizer->isValidDomain(''));
        $this->assertFalse($this->normalizer->isValidDomain('not a domain'));
    }

    public function test_apex_domain_detection(): void
    {
        $this->assertTrue($this->normalizer->isApexDomain('example.com'));
        $this->assertTrue($this->normalizer->isApexDomain('example.co.uk'));
        $this->assertFalse($this->normalizer->isApexDomain('shop.example.com'));
        $this->assertFalse($this->normalizer->isApexDomain('shop.example.co.uk'));
    }

    public function test_host_matches_domain_for_apex_and_subdomains(): void
    {
        // Apex target matches itself, www and any subdomain
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://example.com', 'example.com'));
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://www.example.com', 'example.com'));
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://shop.example.com/product', 'example.com'));
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://blog.example.com', 'example.com'));

        // Must not match unrelated domains that merely share a substring
        $this->assertFalse($this->normalizer->hostMatchesDomain('https://notexample.com', 'example.com'));
        $this->assertFalse($this->normalizer->hostMatchesDomain('https://example.com.evil.com', 'example.com'));

        // Explicit subdomain target only matches that exact subdomain
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://shop.example.com', 'shop.example.com'));
        $this->assertFalse($this->normalizer->hostMatchesDomain('https://example.com', 'shop.example.com'));
        $this->assertFalse($this->normalizer->hostMatchesDomain('https://blog.example.com', 'shop.example.com'));

        // UK second-level suffix treated as apex
        $this->assertTrue($this->normalizer->hostMatchesDomain('https://shop.example.co.uk', 'example.co.uk'));
    }
}
