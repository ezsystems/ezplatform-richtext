<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * RichText Template converter injects rendered template payloads into template elements.
 */
class Template extends Render implements Converter
{
    const LITERAL_LAYOUT_LINE_BREAK = "\n";

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter
     */
    private $richTextConverter;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * RichText Template converter constructor.
     *
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface $renderer
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\Converter $richTextConverter
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RendererInterface $renderer,
        Converter $richTextConverter,
        LoggerInterface $logger = null
    ) {
        $this->richTextConverter = $richTextConverter;
        $this->logger = $logger ?? new NullLogger();

        parent::__construct($renderer);
    }

    /**
     * Injects rendered payloads into template elements.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        // internal templates processed inside a processTemplate
        $xpathExpression = '//docbook:eztemplate[not(ancestor::docbook:eztemplate)] | //docbook:eztemplateinline[not(ancestor::docbook:eztemplateinline)]';

        foreach ($xpath->query($xpathExpression) as $template) {
            $this->processTemplate($document, $xpath, $template);
        }

        return $document;
    }

    /**
     * Processes given template $template in a given $document.
     *
     * @param \DOMDocument $document
     * @param \DOMXPath $xpath
     * @param \DOMElement $template
     */
    protected function processTemplate(DOMDocument $document, DOMXPath $xpath, DOMElement $template)
    {
        $templateName = $template->getAttribute('name');
        $templateType = $template->hasAttribute('type') ? $template->getAttribute('type') : 'tag';
        $parameters = [
            'name' => $templateName,
            'params' => $this->extractConfiguration($template),
        ];

        $contentNodes = $xpath->query('./docbook:ezcontent', $template);
        $innerContent = '';
        foreach ($contentNodes as $contentNode) {
            $innerContent .= $this->getCustomTemplateContent($contentNode);
        }
        $parameters['content'] = !empty($innerContent) ? $innerContent : null;

        foreach ($this->getAttributesMap() as $attribute => $param) {
            if ($template->hasAttribute($attribute)) {
                $parameters[$param] = $template->getAttribute($attribute);
            }
        }

        $content = $this->renderer->renderTemplate(
            $templateName,
            $templateType,
            $parameters,
            $template->localName === 'eztemplateinline'
        );

        if ($content !== null) {
            $payload = $document->createElement('ezpayload');
            $payload->appendChild($document->createCDATASection($content));
            $template->appendChild($payload);
        }
    }

    /**
     * Provides attributes map in format:
     * [
     *     attribute => param,
     *     ...
     * ].
     */
    protected function getAttributesMap(): array
    {
        return [
            'ezxhtml:align' => 'align',
            'xml:id' => 'id',
        ];
    }

    /**
     * Returns XML fragment string for given converted $node.
     *
     * @param \DOMNode $node
     *
     * @return string
     */
    protected function getCustomTemplateContent(DOMNode $node)
    {
        $innerDoc = new DOMDocument();

        $rootNode = $innerDoc->createElementNS('http://docbook.org/ns/docbook', 'section');
        $innerDoc->appendChild($rootNode);

        $rootNode = $this->wrapContentWithLiteralLayout($rootNode, $node);

        /** @var \DOMNode $child */
        foreach ($node->childNodes as $child) {
            $newNode = $innerDoc->importNode($child, true);
            if ($newNode === false) {
                $this->logger->warning("Failed to import Custom Style content of node '{$child->getNodePath()}'");
                continue;
            }

            $rootNode->appendChild($newNode);
        }

        return trim($this->richTextConverter->convert($innerDoc)->saveHTML());
    }

    /**
     * BC: wrap nested content containing line breaks with "literallayout" DocBook tag,
     * unless literallayout already exists.
     *
     * @param \DOMNode $rootNode
     * @param \DOMNode $node
     *
     * @return \DOMNode
     */
    private function wrapContentWithLiteralLayout(DOMNode $rootNode, DOMNode $node): DOMNode
    {
        if (false === strpos($node->nodeValue, self::LITERAL_LAYOUT_LINE_BREAK)) {
            return $rootNode;
        }

        $xpath = new DOMXPath($node->ownerDocument);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        if ($xpath->query('.//docbook:literallayout', $node)->length > 0) {
            return $rootNode;
        }

        $literalLayoutNode = $rootNode->ownerDocument->createElementNS(
            'http://docbook.org/ns/docbook',
            'literallayout'
        );

        return $rootNode->appendChild($literalLayoutNode);
    }
}
