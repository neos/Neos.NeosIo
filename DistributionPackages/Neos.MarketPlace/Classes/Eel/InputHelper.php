<?php

declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

use Neos\Eel\ProtectedContextAwareInterface;

final class InputHelper implements ProtectedContextAwareInterface
{
    public function sanitize(mixed $argument): string|null
    {
        if (!is_string($argument)) {
            return null;
        }

        return trim(htmlspecialchars(strip_tags($argument)));
    }

    /**
     * @return list<string>
     */
    public function sanitizeArray(mixed $argument): array
    {
        if (!is_string($argument)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    self::sanitize(...),
                    explode(',', $argument)
                )
            )
        );
    }

    /**
     * Parses a date string with the given format and sets the time to zero
     */
    public function parseDate(mixed $dateString, string $format): \DateTime|null
    {
        if (!is_string($dateString)) {
            return null;
        }

        try {
            $date = \DateTime::createFromFormat($format, $dateString);
        } catch (\ValueError) {
            return null;
        }

        if ($date === false) {
            return null;
        }

        $date->setTime(0, 0);

        return $date;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
