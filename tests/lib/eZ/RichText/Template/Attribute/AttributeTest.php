<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\UnsupportedAttributeTypeException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\BooleanAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\ChoiceAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\NumberAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\StringAttribute;
use PHPUnit\Framework\TestCase;

final class AttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, Attribute::createFromConfig($name, $config));
    }

    public function testCreateFromConfigThrowsUnsupportedAttributeTypeException(): void
    {
        $this->expectException(UnsupportedAttributeTypeException::class);
        $this->expectExceptionMessage('Unsupported attribute type: example');

        $attribute = Attribute::createFromConfig('example', [
            'type' => 'example',
        ]);
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'boolean' => [
            new BooleanAttribute('example'),
            'example',
            [
                'type' => 'boolean',
            ],
        ];

        yield 'choice' => [
            new ChoiceAttribute('example'),
            'example',
            [
                'type' => 'choice',
            ],
        ];

        yield 'link' => [
            new LinkAttribute('example'),
            'example',
            [
                'type' => 'link',
            ],
        ];

        yield 'number' => [
            new NumberAttribute('example'),
            'example',
            [
                'type' => 'number',
            ],
        ];

        yield 'string' => [
            new StringAttribute('example'),
            'example',
            [
                'type' => 'string',
            ],
        ];
    }
}
