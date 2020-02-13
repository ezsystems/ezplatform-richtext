<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\AttributeTransformationFailedException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer\TransformerInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer\TransformerDispatcher;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use PHPUnit\Framework\TestCase;

final class TransformerDispatcherTest extends TestCase
{
    public function testSupports(): void
    {
        $attributeA = $this->createMock(Attribute::class);
        $attributeB = $this->createMock(Attribute::class);

        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $handlerA = $this->createMock(TransformerInterface::class);
        $handlerA->method('supports')->willReturnMap([
            [$template, $attributeA, true],
            [$template, $attributeB, false],
        ]);

        $handlerB = $this->createMock(TransformerInterface::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(TransformerInterface::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new TransformerDispatcher([
            $handlerA,
            $handlerB,
            $handlerC,
        ]);

        $this->assertTrue($dispatcher->supports($template, $attributeA));
        $this->assertFalse($dispatcher->supports($template, $attributeB));
    }

    public function testTransform(): void
    {
        $attribute = $this->createMock(Attribute::class);

        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $handlerA = $this->createMock(TransformerInterface::class);
        $handlerA->method('supports')->willReturnMap([
            [$template, $attribute, true],
        ]);

        $handlerA->method('transform')->with($template, $attribute, 'input')->willReturn('output');

        $handlerB = $this->createMock(TransformerInterface::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(TransformerInterface::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new TransformerDispatcher([$handlerA, $handlerB, $handlerC]);

        $this->assertEquals('output', $dispatcher->transform($template, $attribute, 'input'));
    }

    public function testProcessThrowsAttributeTransformationFailedException(): void
    {
        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getName')->willReturn('bar');

        $handlerA = $this->createMock(TransformerInterface::class);
        $handlerA->method('supports')->willReturn(false);

        $handlerB = $this->createMock(TransformerInterface::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(TransformerInterface::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new TransformerDispatcher([$handlerA, $handlerB, $handlerC]);

        $this->expectException(AttributeTransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to transform template attribute foo::bar: could not find %s for attribute',
            TransformerInterface::class
        ));

        $dispatcher->transform($template, $attribute, 'input');
    }
}
