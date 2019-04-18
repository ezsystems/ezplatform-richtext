<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template\Collection;

use EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template\Extension\Base as BaseExtension;

class Extension
{
    /**
     * Stores extensions grouped by type and identifier.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * @param iterable $extensions List of template extensions
     */
    public function __construct(iterable $extensions)
    {
        foreach ($extensions as $extension) {
            $this->registerExtension($extension);
        }
    }

    /**
     * Registers an template extension.
     *
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template\Extension $extension
     */
    protected function registerExtension(BaseExtension $extension): void
    {
        $type = $extension->getType();
        $identifier = $extension->getIdentifier();

        if (isset($this->extensions[$type]) === false) {
            $this->extensions[$type] = [];
        }

        if (isset($this->extensions[$type][$identifier]) === false) {
            $this->extensions[$type][$identifier] = [];
        }
        $this->extensions[$type][$identifier][] = $extension;
    }

    /**
     * Returns additional parameters (which will be available in the view).
     *
     * @param string $type Type
     * @param string $identifier Identifier
     * @param array $params Current set of parameters
     *
     * @return array
     */
    public function extend(string $type, string $identifier, array $params): array
    {
        $extensions = $this->getExtensions($type, $identifier);

        foreach ($extensions as $extension) {
            $params = array_merge_recursive($params, $extension->extend($params));
        }

        return $params;
    }

    /**
     * Retrieves list of extensions for template with specified type and identifier.
     *
     * @param string $type Type
     * @param string $identifier Identifier
     *
     * @return array<\EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template\Extension>
     */
    protected function getExtensions(string $type, string $identifier): array
    {
        if (isset($this->extensions[$type]) === false) {
            return [];
        }

        return $this->extensions[$type][$identifier] ?? [];
    }
}
