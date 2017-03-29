<?php

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class FundingApiConnector extends AbstractApiConnector
{
    /**
     * @Flow\InjectConfiguration(path="fundingApi", package="Neos.NeosIo")
     *
     * @var array
     */
    protected $apiSettings;

    /**
     * Retrieves data for sold badges and returns array with customers and their badges and the funding types
     *
     * Result:
     *  {
     *      {
     *          "customerName": "great company",
     *          "customerLogo": "http://absolute.link.to/logo-image.png",
     *          "customerSum": 1200,
     *          "badgeType": "MonthlyBronze",
     *          "fundingType": "MonthlyBronze"
     *          "startDate": "2015-10-08",
     *          "endDate": "2016-07-08"
     *      },
     *      ...
     *  }
     *
     * @return array
     */
    public function fetchBadges()
    {
        $cacheKey = $this->getCacheKey('allBadges');
        $result = $this->getItem($cacheKey);
        if ($result === false) {
            $this->systemLogger->log('Fetching badges from Funding Api', LOG_INFO, 1453193835);
            $result = $this->fetchJsonData('getBadges');
            if (is_array($result)) {
                $fundingData = array_reduce($result, function ($carry, $item) {
                    $fundingType = $item['fundingType'];
                    $customerName = strlen($item['customerName']) ? $item['customerName'] : 'Anonymous';

                    // Store all available funding types
                    $carry['badgeTypes'][$fundingType] = true;

                    // Each customer is a object holding badges and badge types
                    if (!array_key_exists($customerName, $carry['customers'])) {
                        $carry['customers'][$customerName] = [
                            'badges' => [],
                            'badgeTypes' => [],
                            'link' => $item['customerLink'],
                            'logo' => $item['customerLogo']
                        ];
                    }
                    if (!in_array($fundingType, $carry['customers'][$customerName]['badgeTypes'])) {
                        $carry['customers'][$customerName]['badgeTypes'][] = $fundingType;
                    }
                    $carry['customers'][$customerName]['badges'][]= $item;
                    return $carry;
                }, [
                    'customers' => [],
                    'badgeTypes' => [],
                ]);

                $this->setItem($cacheKey, $fundingData);
            } else {
                $this->systemLogger->log('Unknown error when fetching badges from Funding Api, see system log', LOG_ERR, 1453193837);
                $result = [];
            }
        }

        return $result;
    }
}
