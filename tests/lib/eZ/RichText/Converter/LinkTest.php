<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Converter;

use EzSystems\EzPlatformRichText\eZ\RichText\Converter\Link;
use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface;
use eZ\Publish\Core\Repository\ContentService;
use eZ\Publish\Core\Repository\LocationService;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use PHPUnit\Framework\TestCase;
use DOMDocument;
use Psr\Log\LoggerInterface;

/**
 * Tests the Link converter.
 */
class LinkTest extends TestCase
{
    private const EXAMPLE_INPUT_LINK = 'ezlocation://106#anchor';
    private const EXAMPLE_OUTPUT_LINK = '/test#anchor';

    /** @var \eZ\Publish\API\Repository\ContentService|\PHPUnit\Framework\MockObject\MockObject */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService|\PHPUnit\Framework\MockObject\MockObject */
    private $locationService;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter|\PHPUnit\Framework\MockObject\MockObject */
    private $urlAliasRouter;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $hrefResolver;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter\Link */
    private $converter;

    protected function setUp(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->contentService
            ->expects($this->never())
            ->method($this->anything());

        $this->locationService = $this->createMock(LocationService::class);
        $this->locationService
            ->expects($this->never())
            ->method($this->anything());

        $this->urlAliasRouter = $this->createMock(UrlAliasRouter::class);
        $this->urlAliasRouter
            ->expects($this->never())
            ->method($this->anything());

        $this->hrefResolver = $this->createMock(HrefResolverInterface::class);
        $this->hrefResolver
            ->method('resolve')
            ->with(self::EXAMPLE_INPUT_LINK)
            ->willReturn(self::EXAMPLE_OUTPUT_LINK);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger
            ->expects($this->never())
            ->method($this->anything());

        $this->converter = new Link(
            $this->locationService,
            $this->contentService,
            $this->urlAliasRouter,
            $this->hrefResolver,
            $this->logger
        );
    }

    /**
     * @dataProvider dataProviderForConvert
     */
    public function testConvert(string $input, string $output): void
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $outputDocument = $this->converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    public function dataProviderForConvert(): iterable
    {
        yield '<link />' => [
            '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezlocation://106#anchor">Link text</link>
  </para>
</section>',
            '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="/test#anchor">Link text</link>
  </para>
</section>',
        ];

        yield '<ezlink />' => [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106#anchor"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106#anchor" href_resolved="/test#anchor"/>
  </ezembed>
</section>',
        ];
    }
}
