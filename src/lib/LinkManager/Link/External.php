<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link;

class External implements Link
{
    /** @var string */
    private $url;

    /** @var string */
    private $fragment;

    public function __construct(
        string $url,
        string $fragment
    ) {
        $this->url = $url;
        $this->fragment = $fragment;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }
}
