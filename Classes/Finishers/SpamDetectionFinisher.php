<?php
declare(strict_types=1);

namespace DL\HoneypotFormField\Finishers;

/*
 * This file is part of the DL.HoneypotFormField package.
 *
 * (c) Daniel Lienert 2018
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\HoneypotFormField\Validation\Validator\TimestampWithHmacValidator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Form\Core\Model\AbstractFinisher;
use Psr\Log\LoggerInterface;

class SpamDetectionFinisher extends AbstractFinisher
{

    /**
     * @Flow\InjectConfiguration(path="cancelSubsequentFinishersOnSpamDetection")
     * @var bool
     */
    protected $cancelSubsequentFinishersOnSpamDetection;

    /**
     * @Flow\InjectConfiguration(path="logSpamFormData")
     * @var bool
     */
    protected $logSpamFormData;

    /**
     * @Flow\InjectConfiguration(path="minimumTimestampAge")
     * @var int
     */
    protected $minimumTimestampAge;

    /**
     * @Flow\InjectConfiguration(path="maximumTimestampAge")
     * @var int
     */
    protected $maximumTimestampAge;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @return void
     * @api
     */
    protected function executeInternal()
    {
        $honeypotFieldIdentifiers = $this->findHoneypotFields();
        $formRuntime = $this->finisherContext->getFormRuntime();
        $fieldValues = $this->finisherContext->getFormValues();

        $isSpam = false;
        $spamReasons = [];

        foreach ($honeypotFieldIdentifiers as $honeypotFieldIdentifier) {
            $honeypotValue = $fieldValues[$honeypotFieldIdentifier] ?? null;

            // Check if the honeypot field structure is valid
            if ($honeypotValue === null || !is_array($honeypotValue)) {
                $isSpam = true;
                $spamReasons[] = sprintf('%s: field is null or not an array', $honeypotFieldIdentifier);
                continue;
            }

            // Layer 1: Empty field validation - must be empty
            if (!isset($honeypotValue['one']) || (string)$honeypotValue['one'] !== '') {
                $isSpam = true;
                $spamReasons[] = sprintf('%s[one]: layer 1 failed (empty field was filled)', $honeypotFieldIdentifier);
            }

            // Layer 2: Server timestamp validation - must be valid and within age limits
            if (!isset($honeypotValue['two'])) {
                $isSpam = true;
                $spamReasons[] = sprintf('%s[two]: layer 2 failed (field not set)', $honeypotFieldIdentifier);
            } else {
                $validationError = $this->validateTimestamp($honeypotValue['two']);
                if ($validationError !== null) {
                    $isSpam = true;
                    $spamReasons[] = sprintf('%s[two]: layer 2 failed (%s)', $honeypotFieldIdentifier, $validationError);
                }
            }

            // Layer 3: JavaScript interaction proof - must be valid and within age limits
            if (!isset($honeypotValue['three'])) {
                $isSpam = true;
                $spamReasons[] = sprintf('%s[three]: layer 3 failed (field not set)', $honeypotFieldIdentifier);
            } else {
                $validationError = $this->validateTimestamp($honeypotValue['three']);
                if ($validationError !== null) {
                    $isSpam = true;
                    $spamReasons[] = sprintf('%s[three]: layer 3 failed (%s)', $honeypotFieldIdentifier, $validationError);
                }
            }
        }

        if ($isSpam) {
            $formRuntime->getFormState()->setFormValue('spamDetected', $isSpam);

            $additionalData = [];
            if ($this->logSpamFormData) {
                $additionalData['formData'] = $fieldValues;
            }

            $this->logger->info(
                sprintf('The submitted form was detected as spam. Reasons: %s', implode('; ', $spamReasons)),
                array_merge($additionalData, LogEnvironment::fromMethodName(__METHOD__))
            );

            $formRuntime->getFormState()->setFormValue('spamMarker', '[SPAM]');
            $formRuntime->getFormState()->setFormValue('spamReasons', implode('; ', $spamReasons));

            if ($this->cancelSubsequentFinishersOnSpamDetection) {
                $this->finisherContext->cancel();
                $this->logger->info('Subsequent finishers are cancelled due to spam detection.', LogEnvironment::fromMethodName(__METHOD__));
            }
        }
    }

    /**
     * Validates a timestamp with HMAC
     *
     * @param mixed $value The timestamp value to validate
     * @return string|null Error message if invalid, null if valid
     */
    protected function validateTimestamp($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return 'value is not a string or is empty';
        }

        $validator = $this->validatorResolver->createValidator(
            TimestampWithHmacValidator::class,
            [
                'minimumAge' => $this->minimumTimestampAge,
                'maximumAge' => $this->maximumTimestampAge
            ]
        );
        $result = $validator->validate($value);

        if ($result->hasErrors()) {
            $errors = $result->getFlattenedErrors();
            $errorMessages = [];
            foreach ($errors as $propertyPath => $propertyErrors) {
                foreach ($propertyErrors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            return implode(', ', $errorMessages);
        }

        return null;
    }

    /**
     * @return string[]
     */
    protected function findHoneypotFields(): array
    {
        $renderables = $this->finisherContext->getFormRuntime()->getFormDefinition()->getRenderablesRecursively();
        $honeyPotFields = [];

        foreach ($renderables as $renderable) {
            if ($renderable->getType() === 'DL.HoneypotFormField:HoneypotField') {
                $honeyPotFields[] = $renderable->getIdentifier();
            }
        }

        return $honeyPotFields;
    }
}
