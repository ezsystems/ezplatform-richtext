<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link;

final class Info
{
    /** @var string */
    private $url;

    /** @var string */
    private $fragment;

    /** @var bool */
    private $isRemote;

    public function __construct(
        string $url,
        string $fragment,
        bool $isRemote
    ) {
        $this->url = $url;
        $this->fragment = $fragment;
        $this->isRemote = $isRemote;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function isRemote(): bool
    {
        return $this->isRemote;
    }
}
