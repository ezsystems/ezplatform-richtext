<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template;

interface TemplateRegistryInterface
{
    public function registerTemplate(Template $template): void;

    public function has(string $name): bool;

    public function get(string $name): Template;

    public function getAll(): iterable;
}
