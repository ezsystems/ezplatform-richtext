<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use PHPUnit\Framework\TestCase;

final class LinkAttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, LinkAttribute::createFromConfig($name, $config));
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'default' => [
            new LinkAttribute('example'),
            'example',
            [
                'type' => 'link',
            ],
        ];

        yield 'custom' => [
            new LinkAttribute('example', true, 'https://ez.no'),
            'example',
            [
                'type' => 'link',
                'required' => true,
                'default_value' => 'https://ez.no',
            ],
        ];
    }
}
