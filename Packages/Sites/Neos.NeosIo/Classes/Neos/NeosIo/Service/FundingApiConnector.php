<?php

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;

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
     * Retrieves data for sold badges
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
            $this->systemLogger->log(sprintf('Fetching badges from Funding Api'), LOG_INFO, 1453193835);
            $result = $this->fetchJsonData('getBadges');
            if (is_array($result)) {
                $fundingTypes = array_reduce($result, function ($carry, $item) {
                    if (!in_array($item['fundingType'], $carry)) {
                        $carry[]= $item['fundingType'];
                    }
                    return $carry;
                }, []);

                $this->setItem($cacheKey, [
                    'entries' => $result,
                    'types' => $fundingTypes,
                ]);
            } else {
                $this->systemLogger->log(sprintf('Unknown error when fetching badges from Funding Api, see system log'), LOG_ERR, 1453193837);
                $result = array();
            }
        }

        return $result;
    }
}
