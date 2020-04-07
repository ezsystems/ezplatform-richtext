<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link\DOM;

use ArrayIterator;
use EzSystems\EzPlatformRichText\LinkManager\Link\External;
use EzSystems\EzPlatformRichText\LinkManager\Link\Internal;
use Iterator;

final class DocumentLinkCollection implements \IteratorAggregate, \Countable
{
    /** @var \DOMDocument */
    private $document;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\Link\DOM\LinkDOMElement[] */
    private $linkDomElements;

    public function __construct(\DOMDocument $document, array $linkDomElements)
    {
        $this->document = $document;
        $this->linkDomElements = $linkDomElements;
    }

    public function getDocument(): \DOMDocument
    {
        return $this->document;
    }

    /**
     * @return \EzSystems\EzPlatformRichText\LinkManager\Link\DOM\LinkDOMElement[]
     */
    public function getLinkDomElements(): array
    {
        return $this->linkDomElements;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->linkDomElements);
    }

    public function getExternalLinks()
    {
        return array_filter($this->linkDomElements, function (LinkDOMElement $DOMElementLink) {
            return $DOMElementLink->getLinkInfo() instanceof External;
        });
    }

    public function getInternalLinks()
    {
        return array_filter($this->linkDomElements, function (LinkDOMElement $DOMElementLink) {
            return $DOMElementLink->getLinkInfo() instanceof Internal;
        });
    }

    public function count(): int
    {
        return \count($this->linkDomElements);
    }
}
