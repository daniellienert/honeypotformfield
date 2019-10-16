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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
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
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

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
        $filledOutHoneypotFields = [];

        foreach ($honeypotFieldIdentifiers as $honeypotFieldIdentifier) {
            if (isset($fieldValues[$honeypotFieldIdentifier]) && (string)$fieldValues[$honeypotFieldIdentifier] !== '') {
                $isSpam = true;
                $filledOutHoneypotFields[] = $honeypotFieldIdentifier;
            }
        }

        if ($isSpam) {
            $formRuntime->getFormState()->setFormValue('spamDetected', $isSpam);
            $this->logger->info(sprintf('The submitted form was detected as spam, as the honeypot form field %s was filled.', implode(', ', $filledOutHoneypotFields)), LogEnvironment::fromMethodName(__METHOD__));

            $formRuntime->getFormState()->setFormValue('spamMarker', '[SPAM]');
            $formRuntime->getFormState()->setFormValue('spamFilledOutHoneypotFields', implode(', ', $filledOutHoneypotFields));

            if ($this->cancelSubsequentFinishersOnSpamDetection) {
                $this->finisherContext->cancel();
                $this->logger->info('Subsequent finishers are cancelled due to spam detection.', LogEnvironment::fromMethodName(__METHOD__));
            }
        }
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
