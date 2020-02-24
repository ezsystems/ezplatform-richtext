<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Generator;
use IteratorAggregate;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class TemplateIterator implements IteratorAggregate, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const LITERAL_LAYOUT_LINE_BREAK = "\n";

    /** @var \DOMDocument */
    private $document;

    public function __construct(\DOMDocument $document)
    {
        $this->document = $document;
        $this->logger = new NullLogger();
    }

    public function getIterator(): Generator
    {
        $xpath = new DOMXPath($this->document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        /** @var \DOMText|\DOMElement $node */
        foreach ($xpath->query('//docbook:eztemplate | //docbook:eztemplateinline') as $node) {
            $name = $node->getAttribute('name');
            $type = $node->hasAttribute('type') ? $node->getAttribute('type') : 'tag';
            $config = $this->extractConfiguration($node);

            $template = new Template($name, $type, $config);
            $template->setDepth($this->getNodeDepth($node));

            if ($node->hasAttribute('ezxhtml:align')) {
                $template->setAlign($node->getAttribute('ezxhtml:align'));
            }

            yield $template;
        }
    }

    /**
     * Extracts configuration hash from embed element.
     */
    private function extractConfiguration(DOMElement $embed): array
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
     */
    private function extractHash(DOMNode $configHash)
    {
        $hash = [];

        foreach ($configHash->childNodes as $node) {
            /** @var \DOMText|\DOMElement $node */
            if ($node->nodeType === XML_ELEMENT_NODE) {
                if ($node->localName === 'link') {
                    // TODO: Value should depend on type
                    return $node->getAttribute('href');
                } else {
                    $key = $node->getAttribute('key');
                    $hash[$key] = new Parameter($key, $node, $this->extractHash($node));
                }
            } elseif ($node->nodeType === XML_TEXT_NODE && !$node->isWhitespaceInElementContent()) {
                return $node->wholeText;
            }
        }

        return $hash;
    }

    /**
     * Returns depth of given $node in a DOMDocument.
     */
    private function getNodeDepth(DomNode $node): int
    {
        // initial depth for top level elements (to avoid "ifs")
        $depth = -2;

        while ($node) {
            ++$depth;
            $node = $node->parentNode;
        }

        return $depth;
    }

    /**
     * Returns XML fragment string for given converted $node.
     *
     * @param \DOMNode $node
     *
     * @return \DOMDocument
     */
    private function getTemplateContent(DOMNode $node): DOMDocument
    {
        $innerDoc = new DOMDocument();

        $rootNode = $innerDoc->createElementNS('http://docbook.org/ns/docbook', 'section');
        $innerDoc->appendChild($rootNode);

        /** @var \DOMNode $child */
        foreach ($node->childNodes as $child) {
            $newNode = $innerDoc->importNode($child, true);
            if ($newNode === false) {
                $this->logger->warning(
                    "Failed to import Custom Style content of node '{$child->getNodePath()}'"
                );
            }

            $rootNode->appendChild($newNode);
        }

        return $innerDoc;
    }
}
