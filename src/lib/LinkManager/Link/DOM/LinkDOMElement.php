<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link\DOM;

use DOMElement;
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;

final class LinkDOMElement
{
    /** @var \DOMElement */
    private $linkDomElement;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\Link\Info */
    private $linkInfo;

    public function __construct(DOMElement $linkDomElement, Info $linkInfo)
    {
        $this->linkDomElement = $linkDomElement;
        $this->linkInfo = $linkInfo;
    }

    public function getLinkDomElement(): DOMElement
    {
        return $this->linkDomElement;
    }

    public function getLinkInfo(): Info
    {
        return $this->linkInfo;
    }
}
