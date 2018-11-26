<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Form\DataTransformer;

use DOMDocument;
use Exception;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory;
use EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface;
use EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RichTextTransformerTest extends TestCase
{
    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $inputHandler;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter|\PHPUnit\Framework\MockObject\MockObject */
    private $docbook2xhtml5editConverter;

    /** @var \EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer */
    private $richTextTransformer;

    protected function setUp(): void
    {
        $this->inputHandler = $this->createMock(InputHandlerInterface::class);
        $this->docbook2xhtml5editConverter = $this->createMock(Converter::class);

        $this->richTextTransformer = new RichTextTransformer(
            // DOMDocumentFactory is final
            new DOMDocumentFactory(),
            $this->inputHandler,
            $this->docbook2xhtml5editConverter
        );
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer::transform
     */
    public function testTransform(): void
    {
        $outputXML = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<section xmlns="http://ez.no/namespaces/ezpublish5/xhtml5/edit"><p>This is a paragraph.</p></section>';

        $outputDocument = new DOMDocument();
        $outputDocument->loadXML($outputXML);

        $inputXML =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para>This is a paragraph.</para></section>';

        $this->docbook2xhtml5editConverter
            ->expects($this->once())
            ->method('convert')
            ->willReturnCallback(function (DOMDocument $doc) use ($inputXML, $outputDocument) {
                $this->assertXmlStringEqualsXmlString($inputXML, $doc);

                return $outputDocument;
            });

        $this->assertXmlStringEqualsXmlString($outputXML, $this->richTextTransformer->transform($inputXML));
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer::transform
     */
    public function testTransformThrowsTransformationFailedException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Argument \'$xmlString\' is invalid: Start tag expected, \'<\' not found');

        $this->richTextTransformer->transform('Invalid XML');
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer::reverseTransform
     */
    public function testReverseTransform(): void
    {
        $inputXML = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '
            <section xmlns="http://ez.no/namespaces/ezpublish5/xhtml5/edit">
              <p>This is a paragraph.</p>
            </section>
            ';

        $outputXML =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0">
                <para>This is a paragraph.</para>
            </section>
            ';

        $outputDocument = new DOMDocument();
        $outputDocument->loadXML($outputXML);

        $this->inputHandler
            ->expects($this->once())
            ->method('fromString')
            ->with($inputXML)
            ->willReturn($outputDocument);

        $this->assertXmlStringEqualsXmlString($outputXML, $this->richTextTransformer->reverseTransform($inputXML));
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextTransformer::reverseTransform
     *
     * @dataProvider dataProviderForReverseTransformTransformationFailedException
     */
    public function testReverseTransformTransformationFailedException(Exception $exception): void
    {
        $value = 'Invalid XML';

        $this->expectException(TransformationFailedException::class);

        $this->inputHandler
            ->expects($this->once())
            ->method('fromString')
            ->with($value)
            ->willThrowException($exception);

        $this->richTextTransformer->reverseTransform($value);
    }

    public function dataProviderForReverseTransformTransformationFailedException()
    {
        return [
            [$this->createMock(NotFoundException::class)],
            [$this->createMock(InvalidArgumentException::class)],
        ];
    }
}
