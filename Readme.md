# Honeypot Field for Neos.Form and Neos.Form.Builder

[![Latest Stable Version](https://poser.pugx.org/dl/honeypotformfield/v/stable)](https://packagist.org/packages/dl/honeypotformfield) [![Total Downloads](https://poser.pugx.org/dl/honeypotformfield/downloads)](https://packagist.org/packages/dl/honeypotformfield) [![License](https://poser.pugx.org/dl/honeypotformfield/license)](https://packagist.org/packages/dl/honeypotformfield)

This package adds an HoneypotField element, which can be used within your forms. This element is rendered
hidden and should never be filled out by a real form user.

A spam detection finisher checks if the form contains such honeypot fields. If any of that fields are
filled out, additional field values are introduced which can be used in the following finishers to handle spam.

## Installation

    composer require dl/honeypotformfield

# Usage

## Using the flow form configuration

```yaml
type: 'Neos.Form:Form'
identifier: 'my-form'
renderables:
  items:
    type: 'Neos.Form:Page'
    identifier: 'my-page'
    renderables:
      name:
        type: 'Neos.Form:SingleLineText'
        identifier: 'name'
      honeyPot:
        type: 'DL.HoneypotFormField:HoneypotField'
        identifier: 'full_name'

finishers:
  spamDetection:
    identifier: 'DL.HoneypotFormField:SpamDetectionFinisher'
```

## Using the Neos Form Builder
**Requires the suggested package neos/form-builder.**

1. Add honeypot form fields (at least one - as many as you like)
2. Add the Spam detection finisher before the finishers, that should use the spam markers.

<img src="Documentation/Images/HoneypotElements.png" alt="Usage of honeypot field and detection finisher" width="50%"/>

The finisher adds the following new formFields to the formState:

| FieldName                   | Value                                                         | Usage                                               |
|-----------------------------|---------------------------------------------------------------|------------------------------------------------------
| spamDetected                | bool true / false when the submitted form is detected as spam | `{formState.formValues.spamDetected}`               |
| spamMarker                  | Contains `[SPAM]` if detected. Can be used in eMails          | `{formState.formValues.spamMarker}`                 |
| spamFilledOutHoneypotFields | Contains the filled honeypot fields                           | `{formState.formValues.spamFilledOutHoneypotFields}`|

### Mark sent mails as spam

These fields can then be used for example to mark mails as spam:

<img src="Documentation/Images/UseSpamMarker.png" alt="Use the spam marker in email header" width="50%"/>


# Settings

### Cancel mail sending on spam detection

When the `cancelSubsequentFinishersOnSpamDetection` setting is set to `true`, subsequent finishers are not executed
when the form was detected as spam.

<img src="Documentation/Images/CancelFinisherExecution.png" alt="Use the spam marker in email header" width="50%"/>

Here the confirmation message is shown but mail sending is cancelled.

### Log form content when detected as spam

In order to debug the spam detection and to see what kind of spam is coming in, you can enable the logging of the complete
form content with setting `logSpamFormData` to true.
