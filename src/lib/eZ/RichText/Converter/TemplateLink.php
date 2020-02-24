<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\TemplateIterator;

final class TemplateLink implements Converter
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface */
    private $templateRegistry;

    public function __construct(TemplateRegistryInterface $templateRegistry)
    {
        $this->templateRegistry = $templateRegistry;
    }

    public function convert(DOMDocument $document): DOMDocument
    {
        return $document;
        $document = clone $document;

        foreach (new TemplateIterator($document) as $template) {
            /** @var \EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\Template $template */
            if (!$this->templateRegistry->has($template->getName())) {
                // Undefined custom tag
                continue;
            }

            $definition = $this->templateRegistry->get($template->getName());
            foreach ($definition->getAttributes() as $attribute) {
                if (!($attribute instanceof LinkAttribute)) {
                    // Attribute is not a Link
                    continue;
                }

                /** @var \EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\Parameter $param */
                $param = $template->getParam($attribute->getName());

                $node = $param->getNode();

                if ($node->firstChild->nodeType === XML_TEXT_NODE) {
                    $link = $document->createElementNS('http://docbook.org/ns/docbook', 'link', $param->getValue());
                    $link->setAttribute('xlink:href', $param->getValue());
                    $link->setAttribute('xlink:title', $param->getValue());
                    $link->setAttribute('xlink:show', 'none');

                    $node->replaceChild($link, $node->firstChild);
                }
            }
        }

        return $document;
    }
}
