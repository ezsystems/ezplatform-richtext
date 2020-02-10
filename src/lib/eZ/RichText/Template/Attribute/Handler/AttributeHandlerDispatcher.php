<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler;

use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Attribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Template;

final class AttributeHandlerDispatcher implements AttributeHandler
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\Handler\AttributeHandler[] */
    private $handlers;

    public function __construct(iterable $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public function supports(Template $customTag, Attribute $attribute): bool
    {
        return $this->findHandler($customTag, $attribute) !== null;
    }

    public function process(Template $customTag, Attribute $attribute, $value)
    {
        $handler = $this->findHandler($customTag, $attribute);
        if ($handler instanceof AttributeHandler) {
            // TODO: Throw exception
        }

        return $handler->process($customTag, $attribute, $value);
    }

    private function findHandler(Template $customTag, Attribute $attribute): ?AttributeHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($customTag, $attribute)) {
                return $handler;
            }
        }

        return null;
    }
}
