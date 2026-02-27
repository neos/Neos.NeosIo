<?php

declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Formatter\NumberFormatter;

class NumberHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject()]
    protected NumberFormatter $numberFormatter;
    #[Flow\Inject()]
    protected I18n\Service $localizationService;

    /**
     * Format the numeric value as a number with grouped thousands, decimal point and
     * precision.
     */
    public function format(float|int $number, string $localeFormatLength = NumbersReader::FORMAT_LENGTH_DEFAULT): string
    {
        $useLocale = $this->localizationService->getConfiguration()->getCurrentLocale();
        return $this->numberFormatter->formatDecimalNumber($number, $useLocale, $localeFormatLength);
    }

    /**
     * Format the numeric value as a number with grouped thousands, decimal point and
     * precision.
     */
    public function formatCurrency(
        float|int $number,
        string $currency = '€',
        string $localeFormatLength = NumbersReader::FORMAT_LENGTH_DEFAULT
    ): string {
        $useLocale = $this->localizationService->getConfiguration()->getCurrentLocale();
        return $this->numberFormatter->formatCurrencyNumber($number, $useLocale, $currency, $localeFormatLength);
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
