<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance;

final class Template
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\Parameter[] */
    private $params;

    /** @var int */
    private $depth;

    /** @var string|null */
    private $align;

    /** @var \DOMDocument[]|null */
    private $content;

    public function __construct(string $name, string $type, array $params = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->params = $params;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasParam(string $name): bool
    {
        return isset($this->params[$name]);
    }

    public function getParam(string $name): Parameter
    {
        return $this->params[$name];
    }

    /**
     * @return \EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\Parameter[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function setAlign(?string $align): void
    {
        $this->align = $align;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param \DOMDocument[]
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
    }
}
