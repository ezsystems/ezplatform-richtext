<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Validator;

use DOMDocument;
use eZ\Publish\SPI\Persistence\Content\Handler as ContentHandler;
use eZ\Publish\SPI\Persistence\Content\Location\Handler as LocationHandler;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLink;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkIterator;
use EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface;

/**
 * Validator for RichText internal format links.
 */
class InternalLinkValidator implements ValidatorInterface
{
    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Handler
     */
    private $contentHandler;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Location\Handler;
     */
    private $locationHandler;

    /**
     * InternalLinkValidator constructor.
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Handler $contentHandler
     * @param \eZ\Publish\SPI\Persistence\Content\Location\Handler $locationHandler
     */
    public function __construct(ContentHandler $contentHandler, LocationHandler $locationHandler)
    {
        $this->contentHandler = $contentHandler;
        $this->locationHandler = $locationHandler;
    }

    /**
     * Extracts and validate internal links.
     *
     * @param \DOMDocument $xml
     *
     * @return array
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function validateDocument(DOMDocument $xml): array
    {
        $errors = [];

        /** @var \DOMElement $link */
        foreach (new InternalLinkIterator($xml) as $link) {
            if (empty($link->getId())) {
                continue;
            }

            if (!$this->validate($link->getScheme(), $link->getId())) {
                $errors[] = $this->getInvalidLinkError($link->getScheme(), $link->getHref());
            }
        }

        return $errors;
    }

    /**
     * Validates following link formats: 'ezcontent://<contentId>', 'ezremote://<contentRemoteId>', 'ezlocation://<locationId>'.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if given $scheme is not supported
     *
     * @param string $scheme
     * @param string $id
     *
     * @return bool
     */
    public function validate($scheme, $id)
    {
        try {
            switch ($scheme) {
                case InternalLink::EZCONTENT_SCHEME:
                    $this->contentHandler->loadContentInfo($id);
                    break;
                case InternalLink::EZREMOTE_SCHEME:
                    $this->contentHandler->loadContentInfoByRemoteId($id);
                    break;
                case InternalLink::EZLOCATION_SCHEME:
                    $this->locationHandler->load($id);
                    break;
                default:
                    throw new InvalidArgumentException($scheme, "Given scheme '{$scheme}' is not supported.");
            }
        } catch (NotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Builds error message for invalid url.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if given $scheme is not supported
     *
     * @param string $scheme
     * @param string $url
     *
     * @return string
     */
    private function getInvalidLinkError($scheme, $url): string
    {
        switch ($scheme) {
            case InternalLink::EZCONTENT_SCHEME:
            case InternalLink::EZREMOTE_SCHEME:
                return sprintf('Invalid link "%s": target content cannot be found', $url);
            case InternalLink::EZLOCATION_SCHEME:
                return sprintf('Invalid link "%s": target location cannot be found', $url);
            default:
                throw new InvalidArgumentException($scheme, "Given scheme '{$scheme}' is not supported.");
        }
    }
}
