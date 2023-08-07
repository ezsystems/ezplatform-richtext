<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;

/**
 * @internal
 *
 * Processes lists nested in <literallayout> tags.
 */
final class LiteralLayoutNestedList implements Converter
{
    private const FALLBACK_NAMESPACE = 'http://docbook.org/ns/docbook';
    private const ORDERED_LIST_TAG = 'orderedlist';
    private const ITEMIZED_LIST_TAG = 'itemizedlist';

    /**
     * For all <itemizedList> and <orderedList> nested in the <literallayout>, move the list after the <literallayout>,
     * so that it is not inside it.
     */
    public function convert(DOMDocument $document): DOMDocument
    {
        $xpath = new DOMXPath($document);
        $xpathExpression = '//ns:literallayout [descendant::ns:orderedlist|descendant::ns:itemizedlist]';
        $xpath->registerNamespace(
            'ns',
            null !== $document->documentElement && !empty($document->documentElement->namespaceURI)
                ? $document->documentElement->namespaceURI
                : self::FALLBACK_NAMESPACE
        );
        $elements = $xpath->query($xpathExpression) ?: [];

        // elements are list of <literallayout> elements
        /** @var DOMElement $element */
        foreach ($elements as $element) {
            /** @var DOMNode $childNode */
            foreach ($element->childNodes as $childNode) {
                if ($this->isNestedListNode($childNode)) {
                    $targetNode = $childNode->parentNode->parentNode;
                    if ($targetNode !== null) {
                        $targetNode->appendChild($childNode);
                    }
                }
            }
        }

        return $document;
    }

    /**
     * @phpstan-assert-if-true !null $childNode->parentNode
     */
    private function isNestedListNode(DOMNode $childNode): bool
    {
        return $childNode instanceof DOMElement
            && ($childNode->tagName === self::ORDERED_LIST_TAG || $childNode->tagName === self::ITEMIZED_LIST_TAG)
            && $childNode->parentNode !== null;
    }
}
