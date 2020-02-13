<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\TemplateNotFoundException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistry;
use PHPUnit\Framework\TestCase;

final class TemplateRegistryTest extends TestCase
{
    public function testHas(): void
    {
        $registry = new TemplateRegistry([
            new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg'),
            new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg'),
            new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg'),
        ]);

        $this->assertTrue($registry->has('foo'));
        $this->assertFalse($registry->has('foobar'));
    }

    public function testGet(): void
    {
        $fooTemplate = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');
        $barTemplate = new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg');
        $bazTemplate = new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg');

        $registry = new TemplateRegistry([$fooTemplate, $barTemplate, $bazTemplate]);

        $this->assertEquals($fooTemplate, $registry->get('foo'));
        $this->assertEquals($barTemplate, $registry->get('bar'));
        $this->assertEquals($bazTemplate, $registry->get('baz'));
    }

    public function testGetThrowsTemplateNotFoundException(): void
    {
        $registry = new TemplateRegistry([
            new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg'),
            new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg'),
            new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg'),
        ]);

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Could not find template foobar');

        $template = $registry->get('foobar');
    }

    public function testGetAll(): void
    {
        $fooTemplate = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');
        $barTemplate = new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg');
        $bazTemplate = new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg');

        $registry = new TemplateRegistry([$fooTemplate, $barTemplate, $bazTemplate]);

        $this->assertEquals(
            [$fooTemplate, $barTemplate, $bazTemplate],
            $registry->getAll()
        );
    }

    public function testRegister(): void
    {
        $registry = new TemplateRegistry([
            new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg'),
            new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg'),
        ]);

        $bazTemplate = new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg');

        $registry->register($bazTemplate);

        $this->assertTrue($registry->has('baz'));
        $this->assertEquals($bazTemplate, $registry->get('baz'));
    }

    public function testCreateFromConfig(): void
    {
        $config = [
            'foo' => [
                'template' => '@ezdesign/foo.html.twig',
                'icon' => 'foo.svg',
                'is_inline' => true,
                'attributes' => [
                ],
            ],
            'bar' => [
                'template' => '@ezdesign/bar.html.twig',
                'icon' => 'bar.svg',
                'is_inline' => false,
                'attributes' => [
                ],
            ],
            'baz' => [
                'template' => '@ezdesign/baz.html.twig',
                'icon' => 'baz.svg',
                'is_inline' => false,
                'attributes' => [
                ],
            ],
        ];

        $this->assertEquals(
            new TemplateRegistry([
                new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg', true),
                new Template('bar', '@ezdesign/bar.html.twig', 'bar.svg'),
                new Template('baz', '@ezdesign/baz.html.twig', 'baz.svg'),
            ]),
            TemplateRegistry::createFromConfig($config)
        );
    }
}
