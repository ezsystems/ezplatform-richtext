<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);
/**
 * This file contains the Aggregate converter class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Converter;

use DOMDocument;
use EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Converter;

/**
 * Aggregate converter converts using configured converters in prioritized order.
 */
class Aggregate implements Converter
{
    /**
     * An array of converters, sorted by priority.
     *
     * @var \EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Converter[]
     */
    protected $converters = [];

    /**
     * @param \EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Converter[] $converters An array of Converters, sorted by priority
     */
    public function __construct(array $converters = [])
    {
        $this->converters = $converters;
    }

    /**
     * Performs conversion of the given $document using configured converters.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        foreach ($this->converters as $converter) {
            $document = $converter->convert($document);
        }

        return $document;
    }
}
