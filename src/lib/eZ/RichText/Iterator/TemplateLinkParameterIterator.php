<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Iterator;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\TemplateIterator;
use Generator;
use IteratorAggregate;

final class TemplateLinkParameterIterator implements IteratorAggregate
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface */
    private $templateRegistry;

    /** @var \DOMDocument */
    private $document;

    public function getIterator(): Generator
    {
        foreach (new TemplateIterator($this->document) as $template) {
            if (!$this->templateRegistry->has($template->getName())) {
                continue;
            }

            $definition = $this->templateRegistry->get($template->getName());
            foreach ($definition->getAttributesOfType(LinkAttribute::class) as $attribute) {
                if ($template->hasParam($attribute->getName())) {
                    yield new TemplateLinkParameterAdapter(
                        $template->getParam($attribute->getName())->getNode()
                    );
                }
            }
        }
    }
}
