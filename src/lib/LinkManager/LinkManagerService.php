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
use EzSystems\EzPlatformRichText\LinkManager\Link\External;
use EzSystems\EzPlatformRichText\LinkManager\Link\Link;

class LinkManagerService
{
    /** @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway */
    private $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * @param \EzSystems\EzPlatformRichText\LinkManager\Link\Link[] $links
     */
    public function addLinks(VersionInfo $versionInfo, Field $field, array $links): void
    {
        $remoteLinks = array_filter($links, function (Link $linkInfo) {
            return $linkInfo instanceof External;
        });

        $urlIdMap = $this->gateway->getUrlIdMap(
            array_map(function (External $linkInfo) {
                return $linkInfo->getUrl();
            }, $remoteLinks)
        );

        $wasUrlLinkedToContent = [];

        foreach ($remoteLinks as $linkInfo) {
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

    /**
     * @param \EzSystems\EzPlatformRichText\LinkManager\Link\Link[] $linksToPreserve
     */
    public function removeAllButLinks(VersionInfo $versionInfo, Field $field, array $linksToPreserve): void
    {
        $remoteLinks = array_filter($linksToPreserve, function (Link $linkInfo) {
            return $linkInfo instanceof External;
        });

        $urlIdMap = $this->gateway->getUrlIdMap(
            array_map(function (External $linkInfo) {
                return $linkInfo->getUrl();
            }, $remoteLinks)
        );

        $this->gateway->unlinkUrl(
            $field->id,
            $versionInfo->versionNo,
            array_values(
                $urlIdMap
            )
        );
    }
}
