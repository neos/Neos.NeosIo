<?php
declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Event related Eel helpers
 */
class EventHelper implements ProtectedContextAwareInterface
{

    /**
     * Groups a list of events by their start date month
     *
     * @param array{ startDate: \DateTimeInterface, ... }[] $events
     * @return array<string, array{ startDate: \DateTimeInterface }[]> in the format ['<month-name1>' => [<event1>, <event2>], '<month-name2>' => [...
     */
    public function groupByMonth(array $events): array
    {
        $eventsByMonth = [];
        foreach ($events as $event) {
            $month = $event['startDate']->format('F');
            if (!\array_key_exists($month, $eventsByMonth)) {
                $eventsByMonth[$month] = [];
            }
            $eventsByMonth[$month][] = $event;
        }
        return $eventsByMonth;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
