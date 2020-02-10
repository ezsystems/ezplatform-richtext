<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface;

final class LinkAttributeHandler implements AttributeHandler
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface */
    private $linkResolver;

    public function __construct(HrefResolverInterface $linkResolver)
    {
        $this->linkResolver = $linkResolver;
    }

    public function supports(Template $customTag, Attribute $attribute): bool
    {
        return $attribute instanceof LinkAttribute;
    }

    public function process(Template $customTag, Attribute $attribute, $value)
    {
        return $this->linkResolver->resolve($value);
    }
}
