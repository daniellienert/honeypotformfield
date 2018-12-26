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

use Neos\Form\Core\Model\AbstractFinisher;

class SpamDetectionFinisher extends AbstractFinisher
{

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

        $formRuntime->getFormState()->setFormValue('spamDetected', $isSpam);
        if ($isSpam) {
            $formRuntime->getFormState()->setFormValue('spamMarker', '[SPAM]');
            $formRuntime->getFormState()->setFormValue('spamFilledOutHoneypotFields', implode(', ', $filledOutHoneypotFields));
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
