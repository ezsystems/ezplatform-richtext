<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template;

use RuntimeException;
use Throwable;

final class TemplateNotFoundException extends RuntimeException
{
    /** @var string */
    private $name;

    public function __construct(string $name, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not find template %s', $name), $code, $previous);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
