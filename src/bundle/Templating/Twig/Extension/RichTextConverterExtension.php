<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\Templating\Twig\Extension;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter as RichTextConverterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RichTextConverterExtension extends AbstractExtension
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter */
    private $richTextOutputConverter;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter */
    private $richTextEditConverter;

    public function __construct(
        RichTextConverterInterface $richTextOutputConverter,
        RichTextConverterInterface $richTextEditConverter
    ) {
        $this->richTextOutputConverter = $richTextOutputConverter;
        $this->richTextEditConverter = $richTextEditConverter;
    }

    public function getName(): string
    {
        return 'ezrichtext.converter';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'ez_richtext_to_html5',
                [$this, 'richTextToHtml5'],
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'ez_richtext_to_html5_edit',
                [$this, 'richTextToHtml5Edit'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Implements the "ez_richtext_to_html5" filter.
     *
     * @param \DOMDocument $xmlData
     *
     * @return string
     */
    public function richTextToHtml5(DOMDocument $xmlData): string
    {
        return $this->richTextOutputConverter->convert($xmlData)->saveHTML() ?: '';
    }

    /**
     * Implements the "ez_richtext_to_html5_edit" filter.
     *
     * @param \DOMDocument $xmlData
     *
     * @return string
     */
    public function richTextToHtml5Edit(DOMDocument $xmlData): string
    {
        return $this->richTextEditConverter->convert($xmlData)->saveHTML() ?: '';
    }
}
