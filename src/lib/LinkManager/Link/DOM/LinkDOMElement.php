<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link\DOM;

use DOMElement;
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;
use EzSystems\EzPlatformRichText\LinkManager\Link\Link;

final class LinkDOMElement
{
    /** @var \DOMElement */
    private $DOMElement;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\Link\Link */
    private $linkInfo;

    public function __construct(DOMElement $DOMElement, Link $linkInfo)
    {
        $this->DOMElement = $DOMElement;
        $this->linkInfo = $linkInfo;
    }

    public function getDomElement(): DOMElement
    {
        return $this->DOMElement;
    }

    public function getLinkInfo(): Link
    {
        return $this->linkInfo;
    }
}
