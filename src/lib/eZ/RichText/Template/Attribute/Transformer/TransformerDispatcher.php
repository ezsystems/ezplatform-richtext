<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer;

use EzSystems\EzPlatformRichText\eZ\RichText\Exception\Template\AttributeTransformationFailedException;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;

final class TransformerDispatcher implements TransformerInterface
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Transformer\TransformerInterface[] */
    private $handlers;

    public function __construct(iterable $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public function supports(Template $template, Attribute $attribute): bool
    {
        return $this->findTransformer($template, $attribute) !== null;
    }

    public function transform(Template $template, Attribute $attribute, $value)
    {
        $handler = $this->findTransformer($template, $attribute);
        if ($handler instanceof TransformerInterface) {
            return $handler->transform($template, $attribute, $value);
        }

        throw new AttributeTransformationFailedException(
            $template->getName(),
            $attribute->getName(),
            sprintf('could not find %s for attribute', TransformerInterface::class)
        );
    }

    private function findTransformer(Template $customTag, Attribute $attribute): ?TransformerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($customTag, $attribute)) {
                return $handler;
            }
        }

        return null;
    }
}
