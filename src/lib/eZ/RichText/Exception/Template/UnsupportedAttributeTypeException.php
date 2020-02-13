<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template;

use RuntimeException;
use Throwable;

final class UnsupportedAttributeTypeException extends RuntimeException
{
    /** @var string */
    private $type;

    public function __construct(string $type, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Unsupported attribute type: %s', $type), $code, $previous);

        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
