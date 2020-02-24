<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText;

use DOMDocument;
use eZ\Publish\API\Repository\Values\Content\Relation;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLink;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkIterator;

final class RelationProcessor
{
    private const EMBED_TAG_NAMES = [
        'ezembedinline', 'ezembed',
    ];

    private const LINK_TAG_NAMES = [
        'link', 'ezlink',
    ];

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \eZ\Publish\API\Repository\Values\Content\Relation::COMMON type relations,
     * there is a service API for handling those.
     *
     * @param \DOMDocument $doc
     *
     * @return array Hash with relation type as key and array of destination content ids as value.
     *
     * Example:
     * <code>
     *  array(
     *      \eZ\Publish\API\Repository\Values\Content\Relation::LINK => array(
     *          "contentIds" => array( 12, 13, 14 ),
     *          "locationIds" => array( 24 )
     *      ),
     *      \eZ\Publish\API\Repository\Values\Content\Relation::EMBED => array(
     *          "contentIds" => array( 12 ),
     *          "locationIds" => array( 24, 45 )
     *      ),
     *      \eZ\Publish\API\Repository\Values\Content\Relation::FIELD => array( 12 )
     *  )
     * </code>
     */
    public function getRelations(DOMDocument $doc): array
    {
        return [
            Relation::LINK => $this->getRelatedObjectIds($doc, self::LINK_TAG_NAMES),
            Relation::EMBED => $this->getRelatedObjectIds($doc, self::EMBED_TAG_NAMES),
        ];
    }

    /**
     * @param \DOMDocument $xml
     * @param array $tags
     *
     * @return array
     */
    private function getRelatedObjectIds(DOMDocument $xml, array $tags): array
    {
        $contentIds = [];
        $locationIds = [];

        foreach (new InternalLinkIterator($xml, $tags, [InternalLink::EZCONTENT_SCHEME]) as $link) {
            if (!empty($link->getId())) {
                $contentIds[] = $link->getId();
            }
        }

        foreach (new InternalLinkIterator($xml, $tags, [InternalLink::EZLOCATION_SCHEME]) as $link) {
            if (!empty($link->getId())) {
                $locationIds[] = $link->getId();
            }
        }

        return [
            'locationIds' => array_unique($locationIds),
            'contentIds' => array_unique($contentIds),
        ];
    }
}
