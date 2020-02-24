<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\InternalLink;

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
final class InternalLinkResolver implements InternalLinkResolverInterface
{
    use LoggerAwareTrait;

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

    public function resolve(InternalLink $link): string
    {
        $hrefResolved = self::EMPTY_HREF;

        $scheme = $link->getId();
        $id = (int)$link->getId();
        $fragment = $link->getFragment();

        if ($scheme === InternalLink::EZCONTENT_SCHEME) {
            try {
                $hrefResolved = $this->generateContentUrl((int) $id) . $fragment;
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
        } elseif ($scheme === InternalLink::EZLOCATION_SCHEME) {
            try {
                $hrefResolved = $this->generateLocationUrl((int) $id) . $fragment;
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
        } elseif ($scheme === InternalLink::EZURL_SCHEME) {
        } else {
            $hrefResolved = $link->getHref();
        }

        return $hrefResolved;
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
