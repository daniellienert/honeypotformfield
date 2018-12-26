# Honeypot Field for Neos.Form.Builder

This package adds an HoneypotField element, which can be used within your forms. This element is rendered
hidden and should never be filled out by a real form user. 

A spam detection finisher checks if the form contains such honeypot fields. If any of that fields are 
filled out, additional field values are introduced which can be used in the following finishers to handle spam.  

## Installation 

    composer require dl/honeypotformfield
    
## Usage

1. Add honeypot form fields (at least one - as many as you like)
2. Add the Spam detection finisher before the finishers, that should use the spam markers.

<img src="Documentation/Images/HoneypotElements.png" alt="Usage of honeypot field and detection finisher" width="50%"/>
