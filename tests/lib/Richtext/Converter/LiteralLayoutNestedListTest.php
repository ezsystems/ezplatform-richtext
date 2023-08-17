<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter;

use Ibexa\FieldTypeRichText\RichText\Converter\LiteralLayoutNestedList;
use PHPUnit\Framework\TestCase;
use DOMDocument;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\Converter\LiteralLayoutNestedList
 */
final class LiteralLayoutNestedListTest extends TestCase
{
    /**
     * @return array<int, array<int, string>>
     */
    public function providerConvert(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
   <para>This is a p</para>
   <para> </para>
   <itemizedlist>
      <listitem>
         <para>item 1</para>
      </listitem>
      <listitem>
         <para>
            <literallayout class="normal">item 2
this is a line 2
this is line 3<itemizedlist><listitem><para>item 3</para></listitem></itemizedlist></literallayout>
         </para>
      </listitem>
   </itemizedlist>
   <itemizedlist>
      <listitem>
         <para> </para>
      </listitem>
   </itemizedlist>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
   <para>This is a p</para>
   <para> </para>
   <itemizedlist>
      <listitem>
         <para>item 1</para>
      </listitem>
      <listitem>
         <para>
            <literallayout class="normal">item 2
this is a line 2
this is line 3</literallayout>
         <itemizedlist><listitem><para>item 3</para></listitem></itemizedlist></para>
      </listitem>
   </itemizedlist>
   <itemizedlist>
      <listitem>
         <para> </para>
      </listitem>
   </itemizedlist>
</section> ',
            ],
        ];
    }

    /**
     * Test conversion of <li> tags which containing <br/> and <ol>/<ul> tags.
     *
     * @dataProvider providerConvert
     */
    public function testConvert(string $input, string $output): void
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $converter = new LiteralLayoutNestedList();

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        self::assertEquals($expectedOutputDocument, $outputDocument);
    }
}
