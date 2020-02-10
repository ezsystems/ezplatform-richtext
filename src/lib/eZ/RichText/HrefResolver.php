<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException as APINotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException as APIUnauthorizedException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class HrefResolver implements HrefResolverInterface
{
    use LoggerAwareTrait;

    private const EZCONTENT_SCHEME = 'ezcontent://';
    private const EZLOCATION_SCHEME = 'ezlocation://';

    private const INTERNAL_URL_PATTERN = '~^(.+://)?([^#]*)?(#.*|\\s*)?$~';
    private const EMPTY_HREF = '#';

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter */
    private $urlAliasRouter;

    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        UrlAliasRouter $urlAliasRouter
    ) {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->urlAliasRouter = $urlAliasRouter;
        $this->logger = new NullLogger();
    }

    public function resolve(string $url): string
    {
        [$scheme, $id, $fragment] = $this->splitUrl($url);

        if ($scheme === self::EZCONTENT_SCHEME) {
            try {
                return $this->generateContentUrl((int) $id) . $fragment;
            } catch (APINotFoundException $e) {
                $this->logger->warning(
                    'While generating links for richtext, could not locate ' .
                    'Content object with ID ' . $id
                );
            } catch (APIUnauthorizedException $e) {
                $this->logger->notice(
                    'While generating links for richtext, unauthorized to load ' .
                    'Content object with ID ' . $id
                );
            }
        } elseif ($scheme === self::EZLOCATION_SCHEME) {
            try {
                return $this->generateLocationUrl((int) $id);
            } catch (APINotFoundException $e) {
                $this->logger->warning(
                    'While generating links for richtext, could not locate ' .
                    'Location with ID ' . $id
                );
            } catch (APIUnauthorizedException $e) {
                $this->logger->notice(
                    'While generating links for richtext, unauthorized to load ' .
                    'Location with ID ' . $id
                );
            }
        }

        return $url ?? self::EMPTY_HREF;
    }

    private function splitUrl(string $url): array
    {
        preg_match(self::INTERNAL_URL_PATTERN, $url, $matches);
        list(, $scheme, $id, $fragment) = $matches;

        return [$scheme, $id, $fragment];
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function generateLocationUrl(int $id): string
    {
        return $this->urlAliasRouter->generate(
            $this->locationService->loadLocation($id)
        );
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function generateContentUrl(int $id): string
    {
        return $this->generateLocationUrl(
            $this->contentService->loadContentInfo($id)->mainLocationId
        );
    }
}
