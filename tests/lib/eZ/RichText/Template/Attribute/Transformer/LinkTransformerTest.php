<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer;

use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer\LinkTransformer;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\StringAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use PHPUnit\Framework\TestCase;

final class LinkTransformerTest extends TestCase
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $hrefResolver;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer\LinkTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->hrefResolver = $this->createMock(HrefResolverInterface::class);
        $this->transformer = new LinkTransformer($this->hrefResolver);
    }

    public function testSupports(): void
    {
        $template = new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg');

        $this->assertTrue($this->transformer->supports($template, new LinkAttribute('example')));
        $this->assertFalse($this->transformer->supports($template, new StringAttribute('example')));
    }

    public function testTrasform(): void
    {
        $this->hrefResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('ezcontent://1')
            ->willReturn('/ez-platform');

        $this->assertEquals(
            '/ez-platform',
            $this->transformer->transform(
                new Template('foo', '@ezdesign/foo.html.twig', 'foo.svg'),
                new LinkAttribute('example'),
                'ezcontent://1'
            )
        );
    }
}
