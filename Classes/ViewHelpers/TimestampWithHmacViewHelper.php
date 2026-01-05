<?php

declare(strict_types=1);

namespace DL\HoneypotFormField\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to generate HMAC-protected timestamps for Fluid templates
 *
 * Usage:
 * {namespace honeypot=DL\HoneypotFormField\ViewHelpers}
 * {honeypot:timestampWithHmac()}
 */
class TimestampWithHmacViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Generates a timestamp with HMAC protection
     *
     * @return string HMAC-protected timestamp
     */
    public function render(): string
    {
        return $this->hashService->appendHmac((string) time());
    }
}
