<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\NumberAttribute;
use PHPUnit\Framework\TestCase;

final class NumberAttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, NumberAttribute::createFromConfig($name, $config));
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'default' => [
            new NumberAttribute('example'),
            'example',
            [
                'type' => 'number',
            ],
        ];

        yield 'custom' => [
            new NumberAttribute('example', true, PHP_INT_MAX),
            'example',
            [
                'type' => 'number',
                'required' => true,
                'default_value' => PHP_INT_MAX,
            ],
        ];
    }
}
