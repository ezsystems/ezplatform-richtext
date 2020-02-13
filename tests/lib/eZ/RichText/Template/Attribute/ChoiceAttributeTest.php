<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\ChoiceAttribute;
use PHPUnit\Framework\TestCase;

final class ChoiceAttributeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForCreateFromConfig
     */
    public function testCreateFromConfig(Attribute $expected, string $name, array $config): void
    {
        $this->assertEquals($expected, ChoiceAttribute::createFromConfig($name, $config));
    }

    public function dataProviderForCreateFromConfig(): iterable
    {
        yield 'default' => [
            new ChoiceAttribute('example'),
            'example',
            [
                'type' => 'choice',
            ],
        ];

        yield 'custom' => [
            new ChoiceAttribute(
                'example',
                [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                ],
                true,
                'A'
            ),
            'example',
            [
                'type' => 'choice',
                'required' => true,
                'choices' => [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                ],
                'default_value' => 'A',
            ],
        ];
    }
}
