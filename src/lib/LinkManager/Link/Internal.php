<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager\Link;

class Internal implements Link
{
    /** @var string */
    private $id;

    /** @var string */
    private $fragment;

    public function __construct(
        string $id,
        string $fragment
    ) {
        $this->id = $id;
        $this->fragment = $fragment;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }
}
