<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory;
use EzSystems\EzPlatformRichText\eZ\RichText\Exception\InvalidXmlException;
use Ibexa\FieldTypeRichText\RichText\XMLSanitizer;
use PHPUnit\Framework\TestCase;

class DOMDocumentFactoryTest extends TestCase
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->domDocumentFactory = new DOMDocumentFactory(new XMLSanitizer());
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory::loadXMLString
     */
    public function testLoadXMLString(): void
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
</section>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);

        self::assertInstanceOf(DOMDocument::class, $doc);
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory::loadXMLString
     */
    public function testLoadXMLStringThrowsInvalidXmlException(): void
    {
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Argument \'$xmlString\' is invalid: Start tag expected, \'<\' not found');

        $this->domDocumentFactory->loadXMLString('This is not XML');
    }

    public function testEntityReferencesThrowsInvalidXmlException(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<root>
    <data>&xxe;</data>
</root>
EOT;

        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Argument \'$xmlString\' is invalid: Entity \'xxe\' not defined');
        $this->domDocumentFactory->loadXMLString($xml);
    }

    public function testEncodedTagContentIsLeftAlone(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<para>By placing your order you agree to our <link>data &amp; privacy regulations</link>.</para>

EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();

        self::assertSame($xml, $docXMLString);
    }

    public function testRemoveEncodedEntities(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<root>
    <data>
        &#x3C;script&#x3E;alert('hack')&#x3C;/script&#x3E;
        data beetween script
        &#x3C;script&#x3E;alert('hack')&#x3C;/script&#x3E;
        &#x3C;style&#x3E;body { background: red; }&#x3C;/style&#x3E;
        <tag>data beetween style</tag>
        <style>
        
        body { background: red; }
        
        </style>
        &#x3C;iframe src="http://malicious.com"&#x3E;&#x3C;/iframe&#x3E;
        &#x3C;object data="malicious.swf"&#x3E;&#x3C;/object&#x3E;
        &#x3C;embed src="malicious.mp4"&#x3E;&#x3C;/embed&#x3E;
    </data>
</root>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        $listOfTags = ['script', 'style', 'iframe', 'object', 'embed'];
        foreach ($listOfTags as $tagName) {
            self::assertStringNotContainsString("<$tagName>", $docXMLString);
            self::assertStringNotContainsString("</$tagName>", $docXMLString);
            self::assertStringNotContainsString("&#x3C;$tagName&#x3E;", $docXMLString);
            self::assertStringNotContainsString("&#x3C;/$tagName&#x3E;", $docXMLString);
        }

        self::assertStringContainsString('data beetween script', $docXMLString);
        self::assertStringContainsString('<tag>data beetween style</tag>', $docXMLString);
    }

    /**
     * @dataProvider dataProviderForHandleDoctype
     */
    public function testHandleDoctype(string $xml, string $stringNotContainsString): void
    {
        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        self::assertStringNotContainsString($stringNotContainsString, $docXMLString);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public function dataProviderForHandleDoctype(): iterable
    {
        yield 'Case insensitive doctype' => [
            <<<EOT
<?xml version="1.0"?>
<!doctype foo [
    <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<root>
    <data>&xxe;</data>
</root>
EOT,
            '<!ENTITY xxe SYSTEM "file:///etc/passwd">',
        ];

        yield 'Remove extra spaces in doctype' => [
            <<<EOT
<?xml version="1.0"?>
<  !DOCTYPE foo [
    <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<root>
    <data>&xxe;</data>
</root>
EOT,
            '<!ENTITY xxe SYSTEM "file:///etc/passwd">',
        ];
    }

    public function testSafeEntity(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<!DOCTYPE section [
    <!ENTITY nbsp "&#160;">
    <!ENTITY iexcl "&#161;">
    <!ENTITY cent "&#162;">
    <!ENTITY pound "&#163;">
    <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>

<root>
    <data>&pound;</data>
</root>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        self::assertStringNotContainsString('file:///etc/passwd', $docXMLString);
        self::assertStringContainsString('<!ENTITY pound "£">', $docXMLString);
    }

    public function testRemoveBillionLaughs(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<!DOCTYPE lolz [
  <!ENTITY lol "lol">
  <!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
  <!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">
]>
<root>
    <data>&lol3;</data>
</root>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        self::assertStringNotContainsString(
            '<!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">',
            $docXMLString
        );
        self::assertStringNotContainsString(
            '<!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">',
            $docXMLString
        );
    }

    public function testRemoveComments(): void
    {
        $xml = <<<EOT
<?xml version="1.0"?>
<root>
    <!-- This is a comment -->
    <data>Data beetween comments</data>
    <!-- This
      is a
      comment -->
     <data>Some text</data>
</root>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        self::assertStringNotContainsString('<!--', $docXMLString);
        self::assertStringNotContainsString('-->', $docXMLString);
        self::assertStringContainsString(' <data>Data beetween comments</data>', $docXMLString);
    }

    public function testConvertCDATAToText(): void
    {
        $xmlInput = <<<XML
<?xml version="1.0"?>
<root>
    <data><![CDATA[Some CDATA content]]></data>
    <info><![CDATA[Another CDATA <tag>content</tag>]]></info>
</root>
XML;

        $expectedOutput = <<<XML
<?xml version="1.0"?>
<root>
    <data>Some CDATA content</data>
    <info>Another CDATA &lt;tag&gt;content&lt;/tag&gt;</info>
</root>

XML;

        $doc = $this->domDocumentFactory->loadXMLString($xmlInput);
        $docXMLString = $doc->saveXML();
        self::assertIsString($docXMLString);
        self::assertXmlStringEqualsXmlString($expectedOutput, $docXMLString);
    }
}
