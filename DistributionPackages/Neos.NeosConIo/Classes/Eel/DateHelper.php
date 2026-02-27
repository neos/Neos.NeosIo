<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Eel;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;

class DateHelper implements ProtectedContextAwareInterface
{
    public function timezone(?\DateTimeInterface $dateTime = null, ?string $timezone = null): ?\DateTimeImmutable
    {
        if ($dateTime === null) {
            return null;
        }
        if ($dateTime instanceof \DateTime) {
            $dateTime = \DateTimeImmutable::createFromMutable($dateTime);
        }
        if (!$timezone) {
            return $dateTime;
        }
        /** @var \DateTimeImmutable $dateTime */
        return $dateTime->setTimezone(new \DateTimeZone($timezone));
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
