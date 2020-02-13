<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler;

use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler\LinkAttributeHandler;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\StringAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use PHPUnit\Framework\TestCase;

final class LinkAttributeHandlerTest extends TestCase
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $hrefResolver;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler\LinkAttributeHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->hrefResolver = $this->createMock(HrefResolverInterface::class);
        $this->handler = new LinkAttributeHandler($this->hrefResolver);
    }

    public function testSupports(): void
    {
        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $this->assertTrue($this->handler->supports($template, new LinkAttribute('example')));
        $this->assertFalse($this->handler->supports($template, new StringAttribute('example')));
    }

    public function testProcess(): void
    {
        $this->hrefResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('ezcontent://1')
            ->willReturn('/ez-platform');

        $this->assertEquals(
            '/ez-platform',
            $this->handler->process(
                new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg'),
                new LinkAttribute('example'),
                'ezcontent://1'
            )
        );
    }
}
