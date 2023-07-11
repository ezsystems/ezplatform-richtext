<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use DOMDocument;
use DOMXPath;

/**
 * Class ListLineBreaks.
 *
 * Processes lists below <literallayout> tag.
 */
class ListLineBreaks implements Converter
{
    /**
     * For all <itemizedList> and <orderedList> below <literallayout>, move the list after the <literallayout>, so that it is not inside it.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        $xpath = new DOMXPath($document);
        $xpathExpression = '//ns:literallayout [descendant::ns:orderedlist|descendant::ns:itemizedlist]';
        $ns = $document->documentElement ? $document->documentElement->namespaceURI ?: '' : '';
        $xpath->registerNamespace('ns', $ns);
        $elements = $xpath->query($xpathExpression) ?: [];

        // elements are list of <literallayout> elements
        foreach ($elements as $element) {
            $listItemToMove = [];
            $listItemNo = 0;
            /** @var \DOMElement $element */
            foreach ($element->childNodes as $childNode) {
                /** @var \DOMNode $childNode */
                if ($childNode instanceof \DOMElement) {
                    if ($childNode->tagName == 'orderedlist' || $childNode->tagName == 'itemizedlist') {
                        $listItemToMove[$listItemNo] = $childNode;
                        ++$listItemNo;
                        continue;
                    }
                }
            }

            $paragraphNode = null;
            foreach ($listItemToMove as $node) {
                /** @var \DomNode $node */
                if ($node->parentNode !== null) {
                    $targetNode = $node->parentNode->parentNode;
                    if ($targetNode !== null) {
                        $targetNode->appendChild($node);
                    }
                }
            }
        }

        return $document;
    }
}
