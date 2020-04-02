<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\LinkManager;

use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway;
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;

class LinkManagerService
{
    /** @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway */
    private $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * @param \EzSystems\EzPlatformRichText\LinkManager\Link\Info[] $links
     */
    public function addLinks(VersionInfo $versionInfo, Field $field, array $links)
    {
        $urlIdMap = $this->gateway->getUrlIdMap(
            array_map(function (Info $linkInfo) {
                return $linkInfo->getUrl();
            }, array_filter($links, function (Info $linkInfo) {
                return $linkInfo->isRemote();
            }))
        );

        $wasUrlLinkedToContent = [];

        foreach ($links as $linkInfo) {
            if (!$linkInfo->isRemote()) {
                continue;
            }
            $url = $linkInfo->getUrl();

            // Insert the same URL only once
            if (!isset($urlIdMap[$url])) {
                $urlIdMap[$url] = $this->gateway->insertUrl($url);
            }
            // Link the same URL only once
            if (!isset($wasUrlLinkedToContent[$url])) {
                $this->gateway->linkUrl(
                    $urlIdMap[$url],
                    $field->id,
                    $versionInfo->versionNo
                );
                $wasUrlLinkedToContent[$url] = true;
            }
        }
    }
}
