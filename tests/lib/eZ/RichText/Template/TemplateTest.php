<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\AttributeNotFoundException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\BooleanAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\NumberAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\StringAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    public function testHasAttribute(): void
    {
        $template = $this->createExampleTemplate('example', [
            new StringAttribute('foo'),
            new NumberAttribute('bar'),
            new BooleanAttribute('baz'),
        ]);

        $this->assertTrue($template->hasAttribute('foo'));
        $this->assertFalse($template->hasAttribute('foobar'));
    }

    public function testGetAttribute(): void
    {
        $template = $this->createExampleTemplate('example', [
            $foo = new StringAttribute('foo'),
            $bar = new NumberAttribute('bar'),
            $baz = new BooleanAttribute('baz'),
        ]);

        $this->assertEquals($foo, $template->getAttribute('foo'));
        $this->assertEquals($bar, $template->getAttribute('bar'));
        $this->assertEquals($baz, $template->getAttribute('baz'));
    }

    public function testGetThrowsAttributeNotFoundException(): void
    {
        $template = $this->createExampleTemplate('example', [
            new StringAttribute('foo'),
            new NumberAttribute('bar'),
            new BooleanAttribute('baz'),
        ]);

        $this->expectException(AttributeNotFoundException::class);
        $this->expectExceptionMessage('Could not find template attribute example::foobar');

        $template->getAttribute('foobar');
    }

    public function testCreateFromConfig(): void
    {
        $config = [
            'template' => '@ezdesign/example.html.twig',
            'icon' => 'example.svg',
            'is_inline' => true,
            'attributes' => [
                'foo' => [
                    'type' => 'string',
                ],
                'bar' => [
                    'type' => 'number',
                ],
                'baz' => [
                    'type' => 'boolean',
                ],
            ],
        ];

        $expectedResult = $this->createExampleTemplate('example', [
            new StringAttribute('foo'),
            new NumberAttribute('bar'),
            new BooleanAttribute('baz'),
        ]);

        $actualResult = Template::createFromConfig('example', $config);

        $this->assertEquals($expectedResult, $actualResult);
    }

    private function createExampleTemplate(string $name, array $attributes): Template
    {
        return new Template($name, "@ezdesign/$name.html.twig", "$name.svg", true, $attributes);
    }
}
