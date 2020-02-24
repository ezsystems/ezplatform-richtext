<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\InternalLink;

use DOMNode;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

final class InternalLink
{
    public const EZCONTENT_SCHEME = 'ezcontent';
    public const EZLOCATION_SCHEME = 'ezlocation';
    public const EZREMOTE_SCHEME = 'ezremote';
    public const EZURL_SCHEME = 'ezurl';

    private const INTERNAL_URL_PATTERN = '~^((.+)://)?([^#]*)?(#.*|\\s*)?$~';

    /** @var \DOMNode */
    private $node;

    /** @var string|null */
    private $scheme;

    /** @var string|null */
    private $id;

    /** @var string|null */
    private $fragment;

    public function __construct(DOMNode $node, ?string $scheme, ?string $id, ?string $fragment)
    {
        $this->node = $node;
        $this->scheme = $scheme;
        $this->id = $id;
        $this->fragment = $fragment;
    }

    public function getHref(): ?string
    {
        return $this->scheme . '://' . $this->id . $this->fragment;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function getNode(): DOMNode
    {
        return $this->node;
    }

    public function __toString(): string
    {
        return (string)$this->getHref();
    }

    /**
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public static function fromString(DOMNode $node, string $href): self
    {
        if (preg_match(self::INTERNAL_URL_PATTERN, $href, $matches) === false) {
            throw new InvalidArgumentException('$href', 'Href doens\'t match pattern');
        }

        list(, , $scheme, $id, $fragment) = $matches;

        return new self($node, $scheme, $id, $fragment);
    }
}
