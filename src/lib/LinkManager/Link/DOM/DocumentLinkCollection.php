<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link\DOM;

final class DocumentLinkCollection
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
}
