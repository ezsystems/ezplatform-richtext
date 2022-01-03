<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Converter\Render;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $rendererMock;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $converterMock;

    public function setUp(): void
    {
        $this->rendererMock = $this->getRendererMock();
        $this->converterMock = $this->getConverterMock();
        parent::setUp();
    }

    public function providerForTestConvert()
    {
        $data = [];

        $inputDirectory = __DIR__ . '/_fixtures/template/input/';
        $outputDirectory = __DIR__ . '/_fixtures/template/output';
        foreach (static::FIXTURES_PARAMETERS as $fixtureName => $parameters) {
            if (!file_exists("{$inputDirectory}/{$fixtureName}.xml")) {
                self::markTestIncomplete(
                    "Missing input fixture: {$inputDirectory}/{$fixtureName}.xml"
                );
            }
            if (!file_exists("{$outputDirectory}/{$fixtureName}.xml")) {
                self::markTestIncomplete(
                    "Missing output fixture: {$outputDirectory}/{$fixtureName}.xml"
                );
            }

            $inputDocument = new DOMDocument();
            $inputDocument->preserveWhiteSpace = false;
            $inputDocument->formatOutput = false;

            $inputDocument->load("{$inputDirectory}/{$fixtureName}.xml");

            $outputDocument = new DOMDocument();
            $outputDocument->preserveWhiteSpace = false;
            $outputDocument->formatOutput = false;

            $outputDocument->load("{$outputDirectory}/{$fixtureName}.xml");

            $data[] = [
                $inputDocument,
                $outputDocument,
                $parameters,
            ];
        }

        return $data;
    }

    /**
     * @dataProvider providerForTestConvert
     */
    public function testConvert(
        DOMDocument $inputDocument,
        DOMDocument $expectedOutputDocument,
        array $expectedRenderParams
    ): void {
        $this->rendererMock->expects($this->never())->method('renderContentEmbed');
        $this->rendererMock->expects($this->never())->method('renderLocationEmbed');

        [
            $convertParameters,
            $convertReturnValues,
            $renderParameters,
            $renderReturnValues
        ] = $this->provideConvertRenderValues($expectedRenderParams);

        $this->converterMock
            ->expects($this->exactly(count($convertReturnValues)))
            ->method('convert')
            ->withConsecutive(...$convertParameters)
            ->willReturnOnConsecutiveCalls(...$convertReturnValues);

        $this->rendererMock
            ->expects($this->exactly(count($renderReturnValues)))
            ->method('renderTemplate')
            ->withConsecutive(...$renderParameters)
            ->willReturnOnConsecutiveCalls(...$renderReturnValues);

        $outputDocument = $this->getConverter()->convert($inputDocument);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    private function provideConvertRenderValues(array $expectedValues): array
    {
        $convertParameters = [];
        $convertReturnValues = [];
        $renderParameters = [];
        $renderReturnValues = [];

        foreach ($expectedValues as $values) {
            if (!empty($values['params']['content'])) {
                $contentDoc = new DOMDocument();

                $xml = '<section xmlns="http://docbook.org/ns/docbook">';
                $xml .= $values['params']['content'];
                $xml .= '</section>';

                $values['params']['content'] = $xml;

                $fragment = $contentDoc->createDocumentFragment();
                $fragment->appendXML($xml);

                $contentDoc->appendChild($fragment);

                $convertParameters[] = [$contentDoc];
                $convertReturnValues[] = $contentDoc;
            } else {
                $values['params']['content'] = null;
            }

            $renderParameters[] = [
                $values['name'],
                $values['type'] ?? 'tag',
                $values['params'],
                $values['is_inline'],
            ];

            $renderReturnValues[] = $values['name'];
        }

        return [
            $convertParameters,
            $convertReturnValues,
            $renderParameters,
            $renderReturnValues,
        ];
    }

    protected function getConverter()
    {
        return new Template(
            $this->rendererMock,
            new Converter\Aggregate([
                new Template($this->rendererMock, $this->converterMock),
                $this->converterMock,
            ])
        );
    }

    /**
     * @return \EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRendererMock()
    {
        return $this->createMock(RendererInterface::class);
    }

    /**
     * @return \EzSystems\EzPlatformRichText\eZ\RichText\Converter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConverterMock()
    {
        return $this->createMock(Converter::class);
    }

    /**
     * Expected Template parameters for each test fixture (key is a fixture name).
     */
    public const FIXTURES_PARAMETERS = [
        '00-block' => [
            [
                'name' => 'template1',
                'is_inline' => false,
                'params' => [
                    'name' => 'template1',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '01-complex-config' => [
            [
                'name' => 'template2',
                'is_inline' => false,
                'params' => [
                    'name' => 'template2',
                    'content' => 'content2',
                    'params' => [
                        'size' => 'medium',
                        'offset' => 10,
                        'limit' => 5,
                        'hey' => [
                            'look' => [
                                'at' => [
                                    'this' => 'wohoo',
                                    'that' => 'weeee',
                                ],
                            ],
                            'what' => 'get to the chopper',
                        ],
                    ],
                ],
                'content' => null,
            ],
        ],
        '02-block-inline' => [
            [
                'name' => 'template3',
                'is_inline' => false,
                'params' => [
                    'name' => 'template3',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
            [
                'name' => 'template4',
                'is_inline' => true,
                'params' => [
                    'name' => 'template4',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '03-inline' => [
            [
                'name' => 'template6',
                'is_inline' => true,
                'params' => [
                    'name' => 'template6',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '04-block-nested-template' => [
            [
                'name' => 'template8',
                'is_inline' => false,
                'params' => [
                    'name' => 'template8',
                    'content' => 'content8',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
            [
                'name' => 'template7',
                'is_inline' => false,
                'params' => [
                    'name' => 'template7',
                    'content' => 'content7<eztemplate name="template8"><ezcontent>content8</ezcontent><ezpayload>template8</ezpayload></eztemplate>',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '05-block-content-config' => [
            [
                'name' => 'custom_tag',
                'is_inline' => false,
                'params' => [
                    'name' => 'custom_tag',
                    'content' => '<para>Param: value</para>',
                    'params' => [
                        'param' => 'value',
                    ],
                    'align' => 'right',
                ],
                'content' => null,
            ],
        ],
        '06-custom-style-block' => [
            [
                'name' => 'style1',
                'type' => 'style',
                'is_inline' => false,
                'params' => [
                    'name' => 'style1',
                    'content' => 'style 1 content',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '07-custom-style-block-inline' => [
            [
                'name' => 'style2',
                'type' => 'style',
                'is_inline' => false,
                'params' => [
                    'name' => 'style2',
                    'content' => 'style 2 content',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
            [
                'name' => 'style3',
                'type' => 'style',
                'is_inline' => true,
                'params' => [
                    'name' => 'style3',
                    'content' => 'style 3 content',
                    'params' => [
                    ],
                ],
                'content' => null,
            ],
        ],
        '08-line-breaks' => [
            [
                'name' => 'template8',
                'type' => 'tag',
                'is_inline' => false,
                'params' => [
                    'name' => 'template8',
                    'content' => "<literallayout>Some content\nwith line breaks.</literallayout>",
                    'params' => [],
                ],
                'content' => null,
            ],
        ],
    ];
}
