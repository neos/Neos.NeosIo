<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\QueryInterface;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel;
use Neos\ContentRepository\Search\Search\QueryBuilderInterface;

/**
 * ElasticSearchQueryBuilder
 */
class ElasticSearchQueryBuilder extends Eel\ElasticSearchQueryBuilder
{
    protected bool $hasFulltext = false;

    /**
     * @return QueryInterface
     */
    public function getRequest(): QueryInterface
    {
        $request = parent::getRequest();
        $copiedRequest = clone $request;
        if ($this->hasFulltext !== false) {
            self::enforceFunctionScoring($copiedRequest);
        }

        return $copiedRequest;
    }

    /**
     * @param array{} $options Options to configure the query_string, see https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-query-string-query.html
     */
    public function fulltext(string $searchWord, array $options = []): QueryBuilderInterface
    {
        $searchWord = str_replace('/', '\\/', trim($searchWord));
        if ($searchWord === '') {
            return $this;
        }
        $this->hasFulltext = true;

        $this->request->setValueByPath('query.bool.filter.bool.minimum_should_match', '1');
        $this->request->setValueByPath('query.bool.filter.bool.should', [
            'multi_match' => [
                'fields' => [
                    'title^10',
                    '__title^10',
                    '__composerVendor^5',
                    '__maintainers.name^5',
                    '__maintainers.tag^8',
                    'description^2',
                    'lastVersion.keywords.name^10',
                    'lastVersion.keywords.tag^12',
                    'neos_fulltext.*'
                ],
                'query' => $searchWord,
                'operator' => 'AND'
            ]
        ]);

        return $this;
    }


    protected static function enforceFunctionScoring(QueryInterface $request): void
    {
        $request->setValueByPath('query',
            [
                'function_score' => [
                    'functions' => [
                        [
                            'filter' => [
                                'term' => [
                                    'neos_type_and_supertypes' => 'Neos.MarketPlace:Vendor'
                                ],
                            ],
                            'weight' => 1.2
                        ],
                        [
                            'field_value_factor' => [
                                'field' => 'downloadDaily',
                                'factor' => 0.5,
                                'modifier' => 'sqrt',
                                'missing' => 1
                            ]
                        ],
                        [
                            'field_value_factor' => [
                                'field' => 'githubStargazers',
                                'factor' => 1,
                                'modifier' => 'sqrt',
                                'missing' => 1
                            ]
                        ],
                        [
                            'field_value_factor' => [
                                'field' => 'githubForks',
                                'factor' => 0.5,
                                'modifier' => 'sqrt',
                                'missing' => 1
                            ]
                        ],
                        [
                            'gauss' => [
                                'lastVersion.time' => [
                                    'scale' => '60d',
                                    'offset' => '5d',
                                    'decay' => 0.5
                                ]
                            ]
                        ]
                    ],
                    'score_mode' => 'avg',
                    'boost_mode' => 'multiply',
                    'query' => $request->toArray()['query']
                ]
            ]);
    }
}
