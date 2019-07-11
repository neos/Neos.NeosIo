<?php
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
     * @param array $events
     * @return array in the format ['<month-name1>' => [<event1>, <event2>], '<month-name2>' => [...
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
