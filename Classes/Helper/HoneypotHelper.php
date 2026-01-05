<?php

declare(strict_types=1);

namespace DL\HoneypotFormField\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Cryptography\HashService;

/**
 * Eel helper for honeypot form fields
 *
 * Provides utilities for generating HMAC-protected timestamps
 * used in the three-layer honeypot spam protection mechanism.
 */
class HoneypotHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Generates a timestamp with HMAC protection
     *
     * This is used for the second and third layer of honeypot protection:
     * - Layer 2: Server-generated timestamp (pre-filled)
     * - Layer 3: JavaScript-filled timestamp (on user interaction)
     *
     * @return string HMAC-protected timestamp
     */
    public function timestampWithHmac(): string
    {
        return $this->hashService->appendHmac((string) time());
    }

    /**
     * Determines which methods are callable from within Eel
     *
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
