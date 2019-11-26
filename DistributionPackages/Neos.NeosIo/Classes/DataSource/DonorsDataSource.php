<?php
declare(strict_types=1);

namespace Neos\NeosIo\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\NeosIo\Service\FundingApiConnector;

class DonorsDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'neos-neosio-donors';

    /**
     * @Flow\Inject
     * @var FundingApiConnector
     */
    protected $fundingApi;

    /**
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return array
     */
    public function getData(NodeInterface $node = null, array $arguments = []): array
    {
        $options = [];

        $badges = $this->fundingApi->fetchBadges();

        if (array_key_exists('customers', $badges)) {
            foreach ($badges['customers'] as $customerName => $customerData) {
                $options[$customerName] = ['label' => $customerName];
            }
            ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $options;
    }
}
