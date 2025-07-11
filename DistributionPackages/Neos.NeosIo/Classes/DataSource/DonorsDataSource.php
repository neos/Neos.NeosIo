<?php
declare(strict_types=1);

namespace Neos\NeosIo\DataSource;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\NeosIo\Service\FundingApiConnector;

class DonorsDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'neos-neosio-donors';

    #[Flow\Inject]
    protected FundingApiConnector $fundingApi;

    /**
     * @param array{} $arguments Additional arguments (key / value)
     * @return array<string, array{label: string}> An array of options for the donor data source
     */
    public function getData(Node $node = null, array $arguments = []): array
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
