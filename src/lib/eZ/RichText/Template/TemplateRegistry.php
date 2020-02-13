<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\TemplateNotFoundException;

final class TemplateRegistry implements TemplateRegistryInterface
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Template[] */
    private $templates;

    public function __construct(iterable $templates = [])
    {
        $this->templates = [];
        foreach ($templates as $template) {
            $this->templates[$template->getName()] = $template;
        }
    }

    public function register(Template $template): void
    {
        $this->templates[$template->getName()] = $template;
    }

    public function has(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    public function get(string $name): Template
    {
        if ($this->has($name)) {
            return $this->templates[$name];
        }

        throw new TemplateNotFoundException($name);
    }

    public function getAll(): iterable
    {
        return array_values($this->templates);
    }

    public static function createFromConfig(array $config): self
    {
        $tags = [];
        foreach ($config as $name => $tagConfig) {
            $tags[] = Template::createFromConfig($name, $tagConfig);
        }

        return new self($tags);
    }
}
