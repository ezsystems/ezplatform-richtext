<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;
use EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface;

final class LinkTransformer implements TransformerInterface
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\HrefResolverInterface */
    private $hrefResolver;

    public function __construct(HrefResolverInterface $linkResolver)
    {
        $this->hrefResolver = $linkResolver;
    }

    public function supports(Template $template, Attribute $attribute): bool
    {
        return $attribute instanceof LinkAttribute;
    }

    public function transform(Template $template, Attribute $attribute, $value)
    {
        return $this->hrefResolver->resolve($value);
    }
}
