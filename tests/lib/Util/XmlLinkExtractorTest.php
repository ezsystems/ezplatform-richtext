<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Util;

use EzSystems\EzPlatformRichText\LinkManager\Link\Info;
use EzSystems\EzPlatformRichText\LinkManager\Extractor\RichTextLinkExtractor;
use PHPUnit\Framework\TestCase;

class XmlLinkExtractorTest extends TestCase
{
    /** @var \EzSystems\EzPlatformRichText\LinkManager\Extractor\RichTextLinkExtractor */
    private $extractor;

    protected function setUp(): void
    {
        $this->extractor = new RichTextLinkExtractor();
    }

    /**
     * @dataProvider provideDocumentAndExpectedList
     */
    public function testGetLinkInfoList(string $xml, array $expectedList)
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $this->assertEquals(
            $expectedList,
            $this->extractor->getLinkInfoList($document)
        );
    }

    public function provideDocumentAndExpectedList()
    {
        return [
            'standard' => [
                '<?xml version="1.0" encoding="UTF-8"?>
                <section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
                    <para>
                        <link xlink:href="http://ez.no#fragment1">External link</link>
                    </para>
                </section>',
                [
                    new Info(
                        'http://ez.no',
                        '#fragment1',
                        true
                    ),
                ],
            ],
            'do_not_extract' => [
                '<?xml version="1.0" encoding="UTF-8"?>
                <section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
                <para>
                    <link xlink:href="ezurl://123#fragment1">Existing external link</link>
                </para>
                <para>
                    <link xlink:href="#">Non-existing external link</link>
                </para>
                <para>
                    <link xlink:href="ezlocation://321#fragment1">Existing external link</link>
                </para>
                <para>
                    <link xlink:href="ezcontent://qazwsx#fragment1">Existing external link</link>
                </para>
                </section>',
                [
                ],
            ],
            'missing_links' => [
                '<?xml version="1.0" encoding="UTF-8"?>
                <section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
                    <para>Oh links, where art thou?</para>
                </section>',
                [
                ],
            ],
            'remote_links' => [
                '<?xml version="1.0" encoding="UTF-8"?>
                <section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
                    <para>
                        <link xlink:href="ezremote://abcdef789#fragment1">Content link</link>
                    </para>
                </section>',
                [
                    new Info(
                        'abcdef789',
                        '#fragment1',
                        false
                    ),
                ],
            ],
        ];
    }
}
