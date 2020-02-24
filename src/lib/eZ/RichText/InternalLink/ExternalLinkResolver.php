<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\InternalLink;

use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class ExternalLinkResolver
{
    use LoggerAwareTrait;

    private const EMPTY_HREF = '#';

    /** @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway */
    private $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
        $this->logger = new NullLogger();
    }

    public function resolve(InternalLink $link): string
    {
    }
}
