<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Converter;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\ContentInfo as APIContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

final class HrefResolverTest extends TestCase
{
    private const EXAMPLE_CONTENT_ID = 104;
    private const EXAMPLE_LOCATION_ID = 106;
    private const EXAMPLE_URL_ALIAS = '/example/url/alias';

    /** @var \eZ\Publish\API\Repository\ContentService|\PHPUnit\Framework\MockObject\MockObject */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService|\PHPUnit\Framework\MockObject\MockObject */
    private $locationService;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter|\PHPUnit\Framework\MockObject\MockObject */
    private $urlAliasRouter;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolver */
    private $hrefResolver;

    protected function setUp(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->urlAliasRouter = $this->createMock(UrlAliasRouter::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->hrefResolver = new HrefResolver(
            $this->locationService,
            $this->contentService,
            $this->urlAliasRouter
        );
        $this->hrefResolver->setLogger($this->logger);
    }

    /**
     * @dataProvider dataProviderForResolveLocationLink
     */
    public function testResolveLocationLink(string $input, int $locationId, string $output): void
    {
        $location = $this->createMock(APILocation::class);

        $this->locationService
            ->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo($locationId))
            ->willReturn($location);

        $this->urlAliasRouter
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($location))
            ->willReturn(self::EXAMPLE_URL_ALIAS);

        $this->assertEquals($output, $this->hrefResolver->resolve($input));
    }

    public function dataProviderForResolveLocationLink(): iterable
    {
        yield 'ezlocation' => [
            'ezlocation://' . self::EXAMPLE_LOCATION_ID,
            self::EXAMPLE_LOCATION_ID,
            self::EXAMPLE_URL_ALIAS,
        ];

        yield 'ezlocation with anchor' => [
            'ezlocation://' . self::EXAMPLE_LOCATION_ID . '#anchor',
            self::EXAMPLE_LOCATION_ID,
            self::EXAMPLE_URL_ALIAS . '#anchor',
        ];
    }

    /**
     * @dataProvider dataProviderForResolveBadLocationLink
     */
    public function testResolveBadLocationLink(
        string $input,
        int $locationId,
        Throwable $exception,
        string $logType,
        string $logMessage
    ): void {
        $this->locationService
            ->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo($locationId))
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method($logType)
            ->with($this->equalTo($logMessage));

        $this->assertEquals('#', $this->hrefResolver->resolve($input));
    }

    public function dataProviderForResolveBadLocationLink(): iterable
    {
        yield 'non-existing location' => [
            'ezlocation://' . self::EXAMPLE_LOCATION_ID,
            self::EXAMPLE_LOCATION_ID,
            $this->createMock(NotFoundException::class),
            'warning',
            'While generating links for richtext, could not locate Location with ID ' . self::EXAMPLE_LOCATION_ID,
        ];

        yield 'unauthorized location' => [
            'ezlocation://' . self::EXAMPLE_LOCATION_ID,
            self::EXAMPLE_LOCATION_ID,
            $this->createMock(UnauthorizedException::class),
            'notice',
            'While generating links for richtext, unauthorized to load Location with ID ' . self::EXAMPLE_LOCATION_ID,
        ];
    }

    /**
     * @dataProvider dataProviderForResolveContentLink
     */
    public function testResolveContentLink(string $input, int $contentId, string $output): void
    {
        $contentInfo = $this->createMock(APIContentInfo::class);
        $location = $this->createMock(APILocation::class);

        $contentInfo->expects($this->once())
            ->method('__get')
            ->with($this->equalTo('mainLocationId'))
            ->willReturn(self::EXAMPLE_LOCATION_ID);

        $this->contentService
            ->expects($this->any())
            ->method('loadContentInfo')
            ->with($this->equalTo($contentId))
            ->willReturn($contentInfo);

        $this->locationService
            ->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo(self::EXAMPLE_LOCATION_ID))
            ->willReturn($location);

        $this->urlAliasRouter
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($location))
            ->willReturn(self::EXAMPLE_URL_ALIAS);

        $this->assertEquals($output, $this->hrefResolver->resolve($input));
    }

    public function dataProviderForResolveContentLink(): iterable
    {
        yield 'ezcontent' => [
            'ezcontent://' . self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_URL_ALIAS,
        ];

        yield 'ezcontent with anchor' => [
            'ezcontent://' . self::EXAMPLE_CONTENT_ID . '#anchor',
            self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_URL_ALIAS . '#anchor',
        ];
    }

    /**
     * @dataProvider dataProviderForResolveBadContentLink
     */
    public function testResolveBadContentLink(
        string $input,
        int $contentId,
        Throwable $exception,
        string $logType,
        string $logMessage
    ): void {
        $this->contentService
            ->expects($this->once())
            ->method('loadContentInfo')
            ->with($this->equalTo($contentId))
            ->will($this->throwException($exception));

        $this->logger
            ->expects($this->once())
            ->method($logType)
            ->with($this->equalTo($logMessage));

        $this->assertEquals('#', $this->hrefResolver->resolve($input));
    }

    public function dataProviderForResolveBadContentLink(): iterable
    {
        yield 'non-existing content' => [
            'ezcontent://' . self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_CONTENT_ID,
            $this->createMock(NotFoundException::class),
            'warning',
            'While generating links for richtext, could not locate Content object with ID ' . self::EXAMPLE_CONTENT_ID,
        ];

        yield 'unauthorized location' => [
            'ezcontent://' . self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_CONTENT_ID,
            $this->createMock(UnauthorizedException::class),
            'notice',
            'While generating links for richtext, unauthorized to load Content object with ID ' . self::EXAMPLE_CONTENT_ID,
        ];
    }

    public function testResolveForUnsupportedLink(): void
    {
        $this->assertEquals(
            'https://ez.no',
            $this->hrefResolver->resolve('https://ez.no')
        );
    }
}
