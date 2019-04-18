<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template\Extension;

/**
 * RichText Template (Custom tag/style) base extension.
 */
abstract class Base
{
    /**
     * @param string
     */
    protected $identifier;
    /**
     * @param string
     */
    protected $type;

    /**
     * @param string $identifier Identifier
     * @param string $type Type: tag or style
     */
    public function __construct(string $identifier, string $type)
    {
        $this->identifier = $identifier;
        $this->type = $type;
    }

    /**
     * Identifier getter.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Type getter.
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Returns additional parameters which will be available in the view.
     * You can use injected services here to get them.
     *
     * @param array $params Current set of parameters
     *
     * @return array
     */
    public function extend(array $params): array
    {
        return [];
    }
}
