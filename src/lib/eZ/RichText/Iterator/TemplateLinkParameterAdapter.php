<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Iterator;

final class TemplateLinkParameterAdapter implements LinkAdapter
{
    /** @var \DOMElement */
    private $element;

    public function __construct(\DOMElement $element)
    {
        $this->element = $element;
    }

    public function getHref(): string
    {
        return $this->element->textContent;
    }

    public function setHref(string $href): void
    {
        $this->element->textContent = $href;
    }
}
