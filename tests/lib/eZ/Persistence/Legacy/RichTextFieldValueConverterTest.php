<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\Persistence\Legacy\Tests\Content\FieldValue\Converter;

use eZ\Publish\SPI\Persistence\Content\FieldValue;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue;
use EzSystems\EzPlatformRichText\eZ\Persistence\Legacy\RichTextFieldValueConverter;
use PHPUnit\Framework\TestCase;

/**
 * Test case for RichText converter in Legacy storage.
 *
 * @group fieldType
 * @group ezrichtext
 */
class RichTextFieldValueConverterTest extends TestCase
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\Persistence\Legacy\RichTextFieldValueConverter
     */
    protected $converter;

    /**
     * @var string
     */
    private $docbookString;

    protected function setUp()
    {
        parent::setUp();
        $this->converter = new RichTextFieldValueConverter();
        $this->docbookString = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
  <title>This is a heading.</title>
  <para>This is a paragraph.</para>
</section>

EOT;
    }

    protected function tearDown()
    {
        unset($this->docbookString);
        parent::tearDown();
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\eZ\Persistence\Legacy\RichTextFieldValueConverter::toStorageValue
     */
    public function testToStorageValue()
    {
        $value = new FieldValue();
        $value->data = $this->docbookString;
        $storageFieldValue = new StorageFieldValue();

        $this->converter->toStorageValue($value, $storageFieldValue);
        self::assertSame($this->docbookString, $storageFieldValue->dataText);
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\eZ\Persistence\Legacy\RichTextFieldValueConverter::toFieldValue
     */
    public function testToFieldValue()
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->dataText = $this->docbookString;
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame($this->docbookString, $fieldValue->data);
    }
}
