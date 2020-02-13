<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\StringAttribute;
use PHPUnit\Framework\TestCase;

final class StringAttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, StringAttribute::createFromConfig($name, $config));
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'default' => [
            new StringAttribute('example'),
            'example',
            [
                'type' => 'string',
            ],
        ];

        yield 'custom' => [
            new StringAttribute('example', true, 'Lorem ipsum dolor...'),
            'example',
            [
                'type' => 'string',
                'required' => true,
                'default_value' => 'Lorem ipsum dolor...',
            ],
        ];
    }
}
