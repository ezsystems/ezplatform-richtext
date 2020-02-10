<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template;

use RuntimeException;
use Throwable;

final class AttributeNotFoundException extends RuntimeException
{
    /** @var string */
    private $templateName;

    /** @var string */
    private $attributeName;

    public function __construct(string $templateName, string $attributeName, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not find template attribute %s::%s', $templateName, $attributeName), $code, $previous);

        $this->templateName = $templateName;
        $this->attributeName = $attributeName;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }
}
