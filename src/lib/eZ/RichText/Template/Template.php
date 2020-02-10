<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\AttributeNotFoundException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;

final class Template
{
    /** @var string */
    private $name;

    /** @var string */
    private $template;

    /** @var string */
    private $icon;

    /** @var bool */
    private $inline;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute[] */
    private $attributes;

    public function __construct(string $name, string $template, string $icon, bool $inline, array $attributes)
    {
        $this->name = $name;
        $this->template = $template;
        $this->icon = $icon;
        $this->inline = $inline;

        $this->attributes = [];
        foreach ($attributes as $attribute) {
            $this->attributes[$attribute->getName()] = $attribute;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getAttribute(string $name): Attribute
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }

        throw new AttributeNotFoundException($this->name, $name);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function createFromConfig($name, array $config): self
    {
        $attributes = [];
        foreach ($config['attributes'] ?? [] as $attributeName => $attributeConfig) {
            $attributes[] = Attribute::createFromConfig($attributeName, $attributeConfig);
        }

        return new self(
            $name,
            $config['template'],
            $config['icon'],
            $config['is_inline'] ?? false,
            $attributes
        );
    }
}
