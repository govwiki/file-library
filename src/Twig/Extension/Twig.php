<?php

namespace App\Twig\Extension;

use App\Repository\DocumentRepositoryInterface;

/**
 * Class Twig
 *
 * @package App
 */
class Twig extends \Twig_Extension
{

    /**
     * @var string[]
     */
    const STATE_MAP = [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AS' => 'American Samoa',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FM' => 'Federated States of Micronesia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'GU' => 'Guam',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'MP' => 'Northern Mariana Islands',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'PR' => 'Puerto Rico',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VI' => 'Virgin Islands',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * Twig constructor.
     *
     * @param DocumentRepositoryInterface $repository A DocumentRepositoryInterface
     *                                                instance.
     */
    public function __construct(DocumentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return \Twig_Filter[]
     */
    public function getFilters(): array
    {
        return [
            new \Twig_Filter('prettyStateName', function (string $stateCode): string {
                $preparedStateCode = strtoupper($stateCode);

                if (isset(self::STATE_MAP[$preparedStateCode])) {
                    return self::STATE_MAP[$preparedStateCode];
                }

                return $stateCode;
            }),

            new \Twig_Filter('prettyTypeName', function (string $type): string {
                return $this->repository->getTypeByTypeSlug($type);
            }),
        ];
    }
}
