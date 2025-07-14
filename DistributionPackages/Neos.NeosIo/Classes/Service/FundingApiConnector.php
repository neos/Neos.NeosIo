<?php
declare(strict_types=1);

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;

/**
 * @Flow\Scope("singleton")
 */
class FundingApiConnector extends AbstractApiConnector
{
    #[Flow\InjectConfiguration('fundingApi', 'Neos.NeosIo')]
    protected ?array $apiSettings;

    /**
     * Retrieves data for sold badges and returns array with customers and their badges and the funding types
     *
     * Result:
     *  {
     *      {
     *          "customerName": "great company",
     *          "customerLogo": "http://absolute.link.to/logo-image.png",
     *          "customerSum": 1200,
     *          "customerLink": "http://absolute.link.to",
     *          "badgeType": "MonthlyBronze",
     *          "badgeCategory": "Long Time Supporter",
     *          "fundingType": "MonthlyBronze"
     *          "startDate": "2015-10-08",
     *          "endDate": "2016-07-08"
     *      },
     *      ...
     *  }
     * @return array{ customerName: string, customerLogo: string, customerSum: int, customerLink: string, badgeType: string, badgeCategory: string, fundingType: string, startDate: string, endDate: string }[]
     */
    public function fetchBadges(): array
    {
        $cacheKey = $this->getCacheKey('allBadges');
        $result = $this->getItem($cacheKey);
        if ($result === false) {
            $this->logger->info('Fetching badges from Funding Api', LogEnvironment::fromMethodName(__METHOD__));
            $result = $this->fetchJsonData('getBadges');
            if (is_array($result)) {
                $fundingData = array_reduce($result, function ($carry, $item) {
                    $fundingCategory = $item['badgeCategory'];
                    $customerName = is_string($item['customerName']) && $item['customerName'] !== '' ? $item['customerName'] : 'Anonymous';

                    // Store all available funding types
                    if (!empty($fundingCategory)) {
                        $carry['badgeTypes'][$fundingCategory] = true;
                    }

                    // Each customer is a object holding badges and badge types
                    if (!array_key_exists($customerName, $carry['customers'])) {
                        $carry['customers'][$customerName] = [
                            'badges' => [],
                            'badgeTypes' => [],
                            'link' => $item['customerLink'],
                            'logo' => $item['customerLogo']
                        ];
                    }
                    if (!in_array($fundingCategory, $carry['customers'][$customerName]['badgeTypes'])) {
                        $carry['customers'][$customerName]['badgeTypes'][] = $fundingCategory;
                    }
                    $carry['customers'][$customerName]['badges'][] = $item;
                    return $carry;
                }, [
                    'customers' => [],
                    'badgeTypes' => [],
                ]);

                $this->setItem($cacheKey, $fundingData);
            } else {
                $this->logger->error('Unknown error when fetching badges from Funding Api, see system log', LogEnvironment::fromMethodName(__METHOD__));
                $result = [];
            }
        }

        return $result;
    }
}
