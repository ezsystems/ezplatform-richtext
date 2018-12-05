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
        $xpathExpression = '//docbook:eztemplate | //docbook:eztemplateinline';

        $templates = $xpath->query($xpathExpression);
        $templatesSorted = [];
        foreach ($templates as $template) {
            /** @var \DOMElement $template */
            $depth = $this->getNodeDepth($template);
            $templatesSorted[$depth][] = $template;
        }

        ksort($templatesSorted, SORT_NUMERIC);

        foreach ($templatesSorted as $templates) {
            foreach ($templates as $template) {
                $this->processTemplate($document, $xpath, $template);
            }
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
        $content = null;
        $templateName = $template->getAttribute('name');
        $templateType = $template->hasAttribute('type') ? $template->getAttribute('type') : 'tag';
        $parameters = [
            'name' => $templateName,
            'params' => $this->extractTemplateConfiguration($template, $xpath),
        ];

        $contentNodes = $xpath->query('./docbook:ezcontent', $template);
        $innerContent = '';
        foreach ($contentNodes as $contentNode) {
            $innerContent .= $this->getCustomTemplateContent($contentNode);
        }
        if (!empty($innerContent)) {
            $parameters['content'] = $innerContent;
        }

        if ($template->hasAttribute('ezxhtml:align')) {
            $parameters['align'] = $template->getAttribute('ezxhtml:align');
        }

        $content = $this->renderer->renderTemplate(
            $templateName,
            $templateType,
            $parameters,
            $template->localName === 'eztemplateinline'
        );

        if (isset($content)) {
            // If current tag is wrapped inside another template tag we can't use CDATA section
            // for its content as these can't be nested.
            // CDATA section will be used only for content of root wrapping tag, content of tags
            // inside it will be added as XML fragments.
            if ($this->isWrapped($template)) {
                $fragment = $document->createDocumentFragment();
                $fragment->appendXML(htmlspecialchars($content));
                $template->parentNode->replaceChild($fragment, $template);
            } else {
                $payload = $document->createElement('ezpayload');
                $payload->appendChild($document->createCDATASection($content));
                $template->appendChild($payload);
            }
        }
    }

    /**
     * Returns if the given $node is wrapped inside another template node.
     *
     * @param \DOMNode $node
     *
     * @return bool
     */
    protected function isWrapped(DomNode $node)
    {
        while ($node = $node->parentNode) {
            if ($node->localName === 'eztemplate' || $node->localName === 'eztemplateinline') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns depth of given $node in a DOMDocument.
     *
     * @param \DOMNode $node
     *
     * @return int
     */
    protected function getNodeDepth(DomNode $node)
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
     * @return string
     */
    protected function getCustomTemplateContent(DOMNode $node)
    {
        $innerDoc = new DOMDocument();

        /** @var \DOMNode $child */
        foreach ($node->childNodes as $child) {
            $newNode = $innerDoc->importNode($child, true);
            if ($newNode === false) {
                $this->logger->warning(
                    "Failed to import Custom Style content of node '{$child->getNodePath()}'"
                );
            }
            $innerDoc->appendChild($newNode);
        }

        $convertedInnerDoc = $this->richTextConverter->convert($innerDoc);

        return trim($convertedInnerDoc->saveHTML());
    }

    /**
     * Extract configuration hash from a template.
     *
     * @param \DOMElement $template
     * @param \DOMXPath $xpath
     *
     * @return array
     */
    protected function extractTemplateConfiguration(DOMElement $template, DOMXPath $xpath)
    {
        $configElements = $xpath->query('./docbook:ezconfig', $template);
        if (0 === $configElements->length) {
            return [];
        }

        return $this->extractHash($configElements->item(0));
    }
}
