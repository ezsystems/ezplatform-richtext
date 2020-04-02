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
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;

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
            }, array_filter($links->getLinkDomElements(), function (LinkDOMElement $DOMElementLink) {
                return $DOMElementLink->getLinkInfo()->isRemote();
            }))
        );

        $contentIds = $this->gateway->getContentIds(
            array_map(function (LinkDOMElement $DOMElementLink) {
                return $DOMElementLink->getLinkInfo()->getUrl();
            }, array_filter($links->getLinkDomElements(), function (LinkDOMElement $DOMElementLink) {
                return !$DOMElementLink->getLinkInfo()->isRemote();
            }))
        );

        foreach ($links->getLinkDomElements() as $link) {
            $linkInfo = $link->getLinkInfo();
            $url = $linkInfo->getUrl();
            $fragment = $linkInfo->getFragment();

            if (!$linkInfo->isRemote()) {
                if (!isset($contentIds[$url])) {
                    throw new NotFoundException('Content', $url);
                }
                $href = "ezcontent://{$contentIds[$url]}{$fragment}";
            } else {

                $href = "ezurl://{$urlIdMap[$url]}{$fragment}";
            }

            $link->getLinkDomElement()->setAttribute('xlink:href', $href);
        }

        return $links->getDocument();
    }

    public function atRead(DocumentLinkCollection $links): \DOMDocument
    {
        $idUrlMap = $this->gateway->getIdUrlMap(
            array_map(function (Info $internalLink) {
                return $internalLink->getUrl();
            }, array_filter($links->getLinkDomElements(), function (LinkDOMElement $DOMElementLink) {
                return $DOMElementLink->getLinkInfo()->isRemote();
            }))
        );

        $document = $links->getDocument();
        foreach ($links->getLinkDomElements() as $link) {
            $linkInfo = $link->getLinkInfo();
            $urlId = $linkInfo->getUrl();
            $fragment = $linkInfo->getFragment();

            if (isset($idUrlMap[$urlId])) {
                $href = $idUrlMap[$urlId] . $fragment;
            } else {
                $href = '#';
            }

            $link->getLinkDomElement()->setAttribute('xlink:href', $href);
        }

        return $document;
    }
}
