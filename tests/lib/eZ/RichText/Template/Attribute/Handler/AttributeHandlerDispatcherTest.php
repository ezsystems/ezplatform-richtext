<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\AttributeTransformationFailedException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler\AttributeHandler;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler\AttributeHandlerDispatcher;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use PHPUnit\Framework\TestCase;

final class AttributeHandlerDispatcherTest extends TestCase
{
    public function testSupports(): void
    {
        $attributeA = $this->createMock(Attribute::class);
        $attributeB = $this->createMock(Attribute::class);

        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $handlerA = $this->createMock(AttributeHandler::class);
        $handlerA->method('supports')->willReturnMap([
            [$template, $attributeA, true],
            [$template, $attributeB, false],
        ]);

        $handlerB = $this->createMock(AttributeHandler::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(AttributeHandler::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new AttributeHandlerDispatcher([
            $handlerA,
            $handlerB,
            $handlerC,
        ]);

        $this->assertTrue($dispatcher->supports($template, $attributeA));
        $this->assertFalse($dispatcher->supports($template, $attributeB));
    }

    public function testProcess(): void
    {
        $attribute = $this->createMock(Attribute::class);

        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $handlerA = $this->createMock(AttributeHandler::class);
        $handlerA->method('supports')->willReturnMap([
            [$template, $attribute, true],
        ]);

        $handlerA->method('process')->with($template, $attribute, 'input')->willReturn('output');

        $handlerB = $this->createMock(AttributeHandler::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(AttributeHandler::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new AttributeHandlerDispatcher([$handlerA, $handlerB, $handlerC]);

        $this->assertEquals('output', $dispatcher->process($template, $attribute, 'input'));
    }

    public function testProcessThrowsAttributeTransformationFailedException(): void
    {
        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getName')->willReturn('bar');

        $handlerA = $this->createMock(AttributeHandler::class);
        $handlerA->method('supports')->willReturn(false);

        $handlerB = $this->createMock(AttributeHandler::class);
        $handlerB->method('supports')->willReturn(false);

        $handlerC = $this->createMock(AttributeHandler::class);
        $handlerC->method('supports')->willReturn(false);

        $dispatcher = new AttributeHandlerDispatcher([$handlerA, $handlerB, $handlerC]);

        $this->expectException(AttributeTransformationFailedException::class);
        $this->expectExceptionMessage(sprintf('Unable to transform template attribute foo::bar: could not find %s for attribute', AttributeHandler::class));

        $dispatcher->process($template, $attribute, 'input');
    }
}
