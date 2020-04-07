<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Iterator;

use DOMDocument;
use DOMXPath;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLink;
use Generator;
use IteratorAggregate;

final class InternalLinkIterator implements IteratorAggregate
{
    private const DEFAULT_TAGS = [
        'link', 'ezlink',
    ];

    private const DEFAULT_SCHEMAS = [
        InternalLink::EZCONTENT_SCHEME,
        InternalLink::EZREMOTE_SCHEME,
        InternalLink::EZLOCATION_SCHEME,
    ];

    /** @var \DOMDocument */
    private $document;

    /** @var string[] */
    private $tags;

    /** @var string[] */
    private $schemas;

    public function __construct(
        DOMDocument $document,
        array $tags = self::DEFAULT_TAGS,
        array $schemas = self::DEFAULT_SCHEMAS
    ) {
        $this->document = $document;
        $this->tags = $tags;
        $this->schemas = $schemas;
    }

    /**
     * @return \EzSystems\EzPlatformRichText\eZ\RichText\Iterator\LinkAdapter[]
     */
    public function getIterator(): Generator
    {
        $xpath = new DOMXPath($this->document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        foreach ($this->tags as $tagName) {
            $xpathExpression = $this->getXPathExpressionForTag($tagName);

            foreach ($xpath->query($xpathExpression) as $element) {
                yield new InternalLinkAdapter($element);
            }
        }
    }

    private function getXPathExpressionForTag(string $tagName): string
    {
        $hrefSelector = implode(' or ', array_map(static function (string $schema): string {
            return "starts-with(@xlink:href, '$schema://')";
        }, $this->schemas));

        return sprintf('//docbook:%s[%s]', $tagName, $hrefSelector);
    }
}
