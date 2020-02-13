<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\BooleanAttribute;
use PHPUnit\Framework\TestCase;

final class BooleanAttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, BooleanAttribute::createFromConfig($name, $config));
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'default' => [
            new BooleanAttribute('example'),
            'example',
            [
                'type' => 'boolean',
            ],
        ];

        yield 'custom' => [
            new BooleanAttribute('example', true, true),
            'example',
            [
                'type' => 'boolean',
                'required' => true,
                'default_value' => true,
            ],
        ];
    }
}
