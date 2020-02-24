<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance;

final class Parameter
{
    /** @var string */
    private $name;

    /** @var \DOMNode */
    private $node;

    /** @var mixed|null */
    private $value;

    public function __construct(string $name, \DOMNode $node, $value = null)
    {
        $this->name = $name;
        $this->node = $node;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getNode(): \DOMNode
    {
        return $this->node;
    }
}
