<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter;

use DOMElement;
use DOMNode;
use DOMXPath;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;

/**
 * Base class for Render converters.
 */
abstract class Render
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface
     */
    protected $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Extracts configuration hash from embed element.
     *
     * @param \DOMElement $embed
     *
     * @return array
     */
    protected function extractConfiguration(DOMElement $embed)
    {
        $hash = [];

        $xpath = new DOMXPath($embed->ownerDocument);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $configElements = $xpath->query('./docbook:ezconfig', $embed);

        if ($configElements->length) {
            $hash = $this->extractHash($configElements->item(0));
        }

        return $hash;
    }

    /**
     * Recursively extracts data from XML hash structure.
     *
     * @param \DOMNode $configHash
     *
     * @return array
     */
    protected function extractHash(DOMNode $configHash)
    {
        $hash = [];

        foreach ($configHash->childNodes as $node) {
            /** @var \DOMText|\DOMElement $node */
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $hash[$node->getAttribute('key')] = $this->extractHash($node);
            } elseif ($node->nodeType === XML_TEXT_NODE && !$node->isWhitespaceInElementContent()) {
                return $node->wholeText;
            }
        }

        return $hash;
    }
}
