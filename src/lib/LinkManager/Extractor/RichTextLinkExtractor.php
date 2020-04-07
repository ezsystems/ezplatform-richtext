<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Extractor;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use EzSystems\EzPlatformRichText\LinkManager\Link\DOM\DocumentLinkCollection;
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;
use EzSystems\EzPlatformRichText\LinkManager\Link\DOM\LinkDOMElement;

class RichTextLinkExtractor
{
    // This will select only links with non-empty 'xlink:href' attribute value
    public const SAVE_XPATH_QUERY = <<< XPATH
        //docbook:link[string( @xlink:href )
            and not (
                starts-with( @xlink:href, 'ezurl://' )
                or starts-with( @xlink:href, 'ezcontent://' )
                or starts-with( @xlink:href, 'ezlocation://' )
                or starts-with( @xlink:href, '#' )
            )
        ]
    XPATH;

    public const READ_XPATH_QUERY = <<< XPATH
        //docbook:link[starts-with( @xlink:href, 'ezurl://' )]
        |
        //docbook:ezlink[starts-with( @xlink:href, 'ezurl://' )]
    XPATH;

    private function queryForLinks(DOMDocument $document, string $xpathExpression): DOMNodeList
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        $links = $xpath->query($xpathExpression);

        if (!$links) {
            return new DOMNodeList();
        }

        return $links;
    }

    public function getLinksInDocument(DOMDocument $document): DocumentLinkCollection
    {
        $document = clone $document;
        $links = $this->queryForLinks($document, self::SAVE_XPATH_QUERY);
        $linkInfoList = [];

        /** @var \DOMElement $link */
        foreach ($links as $index => $link) {
            preg_match(
                '~^(ezremote://)?([^#]*)?(#.*|\\s*)?$~',
                $link->getAttribute('xlink:href'),
                $matches
            );

            // No scheme part means is nonremote url
            $linkInfoList[] = new LinkDOMElement(
                $link,
                new Info(
                    $matches[2],
                    '',
                    $matches[3],
                    empty($matches[1])
                )
            );
        }

        return new DocumentLinkCollection($document, $linkInfoList);
    }

    public function getLinkInfoForRead(DOMDocument $document): DocumentLinkCollection
    {
        $links = $this->queryForLinks($document, self::READ_XPATH_QUERY);
        $linkInfoList = [];

        /** @var \DOMElement $link */
        foreach ($links as $index => $link) {
            preg_match(
                '~^ezurl://([^#]*)?(#.*|\\s*)?$~',
                $link->getAttribute('xlink:href'),
                $matches
            );

            // No id part means is nonremote url
            $linkInfoList[] = new LinkDOMElement(
                $link,
                new Info(
                    '',
                    $matches[1],
                    $matches[2],
                    empty($matches[1])
                )
            );
        }

        return new DocumentLinkCollection($document, $linkInfoList);
    }
}
