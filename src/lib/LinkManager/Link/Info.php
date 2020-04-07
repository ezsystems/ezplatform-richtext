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

    /** @var string */
    private $id;

    public function __construct(
        string $url,
        ?string $id = null,
        string $fragment = '',
        bool $isRemote = true
    ) {
        $this->url = $url;
        $this->fragment = $fragment;
        $this->isRemote = $isRemote;
        $this->id = $id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getId(): string
    {
        return $this->id;
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
