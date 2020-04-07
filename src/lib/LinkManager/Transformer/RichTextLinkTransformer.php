<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Transformer;

use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway;
use EzSystems\EzPlatformRichText\LinkManager\Link\DOM\DocumentLinkCollection;
use EzSystems\EzPlatformRichText\LinkManager\Link\DOM\LinkDOMElement;
use EzSystems\EzPlatformRichText\LinkManager\Link\External;
use EzSystems\EzPlatformRichText\LinkManager\Link\Internal;

class RichTextLinkTransformer
{
    /** @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway */
    private $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function atSave(DocumentLinkCollection $links): \DOMDocument
    {
        $urlIdMap = $this->gateway->getUrlIdMap(
            array_map(function (LinkDOMElement $DOMElementLink) {
                return $DOMElementLink->getLinkInfo()->getUrl();
            }, $links->getExternalLinks())
        );

        $contentIds = $this->gateway->getContentIds(
            array_map(function (LinkDOMElement $DOMElementLink) {
                return $DOMElementLink->getLinkInfo()->getId();
            }, $links->getInternalLinks())
        );

        /** @var \EzSystems\EzPlatformRichText\LinkManager\Link\DOM\LinkDOMElement $linkDomElement */
        foreach ($links as $linkDomElement) {
            $link = $linkDomElement->getLinkInfo();
            $href = '#';
            if ($link instanceof Internal) {
                $id = $link->getId();
                if (!isset($contentIds[$id->getId()])) {
                    throw new NotFoundException('Content', $id);
                }
                $href = "ezcontent://{$contentIds[$id]}{$link->getFragment()}";
            } elseif ($link instanceof External) {
                $url = $link->getUrl();
                $href = "ezurl://{$urlIdMap[$url]}{$link->getFragment()}";
            }

            $linkDomElement->getDomElement()->setAttribute('xlink:href', $href);
        }

        return $links->getDocument();
    }

    public function atRead(DocumentLinkCollection $links): \DOMDocument
    {
        $idUrlMap = $this->gateway->getIdUrlMap(
            array_map(function (LinkDOMElement $internalLink) {
                return $internalLink->getLinkInfo()->getId();
            }, $links->getInternalLinks())
        );

        $document = $links->getDocument();
        foreach ($links->getLinkDomElements() as $link) {
            $linkInfo = $link->getLinkInfo();
            $urlId = $linkInfo->getId();
            $fragment = $linkInfo->getFragment();

            if (isset($idUrlMap[$urlId])) {
                $href = $idUrlMap[$urlId] . $fragment;
            } else {
                $href = '#';
            }

            $link->getDomElement()->setAttribute('xlink:href', $href);
        }

        return $document;
    }
}
