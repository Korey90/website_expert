<?php

namespace App\Exceptions\Seo;

use RuntimeException;

/**
 * Thrown when the SERP provider fails, times out, or returns an unexpected payload.
 * Must never be mapped to a "not found" ranking result — the caller shows a
 * "try again" message instead so a provider outage isn't reported as a real result.
 */
class SerpProviderException extends RuntimeException {}
