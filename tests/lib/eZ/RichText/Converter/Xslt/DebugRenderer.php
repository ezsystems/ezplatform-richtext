<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Converter\Xslt;

use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;

final class DebugRenderer implements RendererInterface
{
    private const TEMPLATE_FORMAT = '<template-output name="%s" type="%s" is-inline="%d">%s</template-output>';
    private const EMBED_CONTENT_FORMAT = '<embed-content-output content-id="%d" view-type="%s" is-inline="%s">%s</embed-content-output>';
    private const EMBED_LOCATION_FORMAT = '<embed-location-output location-id="%d" view-type="%s" is-inline="%s">%s</embed-location-output>';

    public function renderTag($name, array $parameters, $isInline): string
    {
        return $this->renderTemplate($name, 'tag', $parameters, $isInline);
    }

    public function renderTemplate($name, $type, array $parameters, $isInline): string
    {
        return sprintf(
            self::TEMPLATE_FORMAT,
            $name,
            $type,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    public function renderContentEmbed($contentId, $viewType, array $parameters, $isInline): string
    {
        return sprintf(
            self::EMBED_CONTENT_FORMAT,
            $contentId,
            $viewType,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    public function renderLocationEmbed($locationId, $viewType, array $parameters, $isInline): string
    {
        return sprintf(
            self::EMBED_LOCATION_FORMAT,
            $locationId,
            $viewType,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    private function serializeParameters(array $parameters): string
    {
        $lines = [];

        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $lines[] = sprintf('<param name="%s">', $name);
                    $lines[] = $this->serializeParameters($value);
                    $lines[] = sprintf('</param>');
                }
            } else {
                $lines[] = sprintf('<param name="%s">%s</param>', $name, $value);
            }
        }

        return implode('', $lines);
    }

    private function serializeIsInline(bool $isInline): string
    {
        return $isInline ? 'true' : 'false';
    }
}
