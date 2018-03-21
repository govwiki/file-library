<?php

namespace App\Twig\Extension;

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
    const SIZE_POSTFIX = [
        'Bytes',
        'KB',
        'MB',
        'GB',
        'TB',
    ];

    /**
     * @return \Twig_Filter[]
     */
    public function getFilters(): array
    {
        return [
            new \Twig_Filter('prettyFileSize', function (int $fileSize): string {
                $nextPrettyFileSize = $fileSize;
                $postfixIdx = 0;

                do {
                    $prettyFileSize = $nextPrettyFileSize;
                    $nextPrettyFileSize /= 1024;
                    $postfixIdx++;
                } while ($nextPrettyFileSize > 1);

                return number_format($prettyFileSize, 1) .' '. self::SIZE_POSTFIX[$postfixIdx - 1];
            }),
        ];
    }
}
