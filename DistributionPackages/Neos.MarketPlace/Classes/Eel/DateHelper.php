<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Utility\Now;

class DateHelper implements ProtectedContextAwareInterface
{
    public function formatRelative(?\DateTime $date, bool $compact = false): string
    {
        if ($date === null) {
            return '';
        }
        $now = new Now();
        // Same day of same year
        if ($date->format('Y z') === $now->format('Y z')) {
            $hours = $date->diff($now)->h;
            if ($hours > 1) {
                return $compact ? $hours . ' hours ago' : 'Last activity ' .  $hours . ' hours ago';
            }

            return $compact ? 'One hour ago' : 'Last activity one hour ago';
        }
        $days = $now->diff($date)->format('%a');
        if ($days < 30) {
            if ($days > 1) {
                return $compact ? $days . ' days ago' : 'Last activity ' .  $days . ' days ago';
            }

            return $compact ? 'Yesterday' : 'Last activity yesterday';
        }

        return $compact ? $date->format('d M Y') : 'Last activity on ' . $date->format('d F Y');
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}

