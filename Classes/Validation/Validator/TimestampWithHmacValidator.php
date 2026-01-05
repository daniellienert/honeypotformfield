<?php

declare(strict_types=1);

namespace DL\HoneypotFormField\Validation\Validator;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException;
use Neos\Flow\Security\Exception\InvalidHashException;
use Neos\Flow\Validation\Validator\AbstractValidator;

/**
 * Validator for timestamps with HMAC protection
 *
 * Validates that a timestamp:
 * - Has a valid HMAC signature
 * - Is not too old (default: 24 hours)
 * - Is not too young (default: 10 seconds)
 *
 * This is used to prevent both form replay attacks and instant bot submissions.
 */
class TimestampWithHmacValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    /**
     * @var array<string, mixed>
     */
    protected $supportedOptions = [
        'minimumAge' => [10, 'Minimum age for the hmacked timestamp in seconds', 'integer'],
        'maximumAge' => [86400, 'Maximum age for the hmacked timestamp in seconds', 'integer']
    ];

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Validates the timestamp with HMAC
     *
     * @param mixed $value The HMAC-protected timestamp string
     * @return void
     */
    protected function isValid($value)
    {
        try {
            $timestamp = $this->hashService->validateAndStripHmac($value);
            $age = time() - (int)$timestamp;

            if ($age > $this->options['maximumAge']) {
                $this->addError(
                    sprintf(
                        'Timestamp is too old: age is %d seconds, maximum allowed is %d seconds',
                        $age,
                        $this->options['maximumAge']
                    ),
                    1696525723
                );
            } elseif ($age < $this->options['minimumAge']) {
                $this->addError(
                    sprintf(
                        'Timestamp is too young: age is %d seconds, minimum required is %d seconds',
                        $age,
                        $this->options['minimumAge']
                    ),
                    1696525724
                );
            }
        } catch (InvalidArgumentForHashGenerationException | InvalidHashException $exception) {
            $this->addError('HMAC validation failed', 1696525725);
        }
    }
}
