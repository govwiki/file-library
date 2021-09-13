<?php
declare(strict_types = 1);

namespace App\Service;

/**
 * States
 */
final class States
{
    /**
     * @var array
     */
    private $options = [
        'AK' => 'Alaska',
        'AL' => 'Alabama',
        'AR' => 'Arkansas',
        'AS' => 'American Samoa',
        'AZ' => 'Arizona',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DC' => 'District of Columbia',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'FM' => 'Federated States of Micronesia',
        'GA' => 'Georgia',
        'GU' => 'Guam',
        'HI' => 'Hawaii',
        'IA' => 'Iowa',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'MA' => 'Massachusetts',
        'MD' => 'Maryland',
        'ME' => 'Maine',
        'MH' => 'Marshall Islands',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MO' => 'Missouri',
        'MP' => 'Northern Mariana Islands',
        'MS' => 'Mississippi',
        'MT' => 'Montana',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'NE' => 'Nebraska',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NV' => 'Nevada',
        'NY' => 'New York',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'PW' => 'Palau',
        'PR' => 'Puerto Rico',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VA' => 'Virginia',
        'VI' => 'Virgin Islands',
        'VT' => 'Vermont',
        'WA' => 'Washington',
        'WI' => 'Wisconsin',
        'WV' => 'West Virginia',
        'WY' => 'Wyoming',
    ];

    /**
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getSortOptions():array
    {
        $array = $this->getOptions();
        \usort($array, function (string $stateNameOne, string $stateNameSecond) {
            return \strcmp($stateNameOne, $stateNameSecond);
        });

        return $array;
    }
}