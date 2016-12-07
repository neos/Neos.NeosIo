<?php
namespace Neos\NeosIo\ViewHelpers;

use TYPO3\Fluid\ViewHelpers\FormViewHelper;

/**
 * A very simple form ViewHelper that doesn't render hidden referrer and "__trustedProperties" fields
 */
class SimpleFormViewHelper extends FormViewHelper
{
    protected function renderHiddenReferrerFields()
    {
        return '';
    }

    protected function renderTrustedPropertiesField()
    {
        return '';
    }

}