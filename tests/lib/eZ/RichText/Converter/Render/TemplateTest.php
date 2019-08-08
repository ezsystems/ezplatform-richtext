<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Converter\Render;

use PHPUnit\Framework\TestCase;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter\Render\Template;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;
use DOMDocument;

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

    public function setUp()
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
     *
     * @param \DOMDocument $inputDocument
     * @param \DOMDocument $expectedOutputDocument
     * @param array $expectedRenderParams
     */
    public function testConvert(DOMDocument $inputDocument, DOMDocument $expectedOutputDocument, array $expectedRenderParams)
    {
        $this->rendererMock->expects($this->never())->method('renderContentEmbed');
        $this->rendererMock->expects($this->never())->method('renderLocationEmbed');

        if (!empty($expectedRenderParams)) {
            $convertIndex = 0;
            foreach ($expectedRenderParams as $index => $params) {
                if (!empty($params['params']['content'])) {
                    // mock simple converter
                    $contentDoc = new DOMDocument();

                    $xml = '<section xmlns="http://docbook.org/ns/docbook">';
                    $xml .= $params['params']['content'];
                    $xml .= '</section>';

                    $params['params']['content'] = $xml;

                    $fragment = $contentDoc->createDocumentFragment();
                    $fragment->appendXML($xml);

                    $contentDoc->appendChild($fragment);

                    $this->converterMock
                        ->expects($this->at($convertIndex++))
                        ->method('convert')
                        ->with($contentDoc)
                        ->willReturn($contentDoc);
                }

                $this->rendererMock
                    ->expects($this->at($index))
                    ->method('renderTemplate')
                    ->with(
                        $params['name'],
                        isset($params['type']) ? $params['type'] : 'tag',
                        $params['params'],
                        $params['is_inline']
                    )
                    ->willReturn($params['name']);
            }
        } else {
            $this->rendererMock->expects($this->never())->method('renderTemplate');
        }

        $outputDocument = $this->getConverter()->convert($inputDocument);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    protected function getConverter()
    {
        return new Template($this->rendererMock, $this->converterMock);
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
    const FIXTURES_PARAMETERS = [
        '00-block' => [
            [
                'name' => 'template1',
                'is_inline' => false,
                'params' => [
                    'name' => 'template1',
                    'params' => [
                    ],
                ],
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
            ],
            [
                'name' => 'template4',
                'is_inline' => true,
                'params' => [
                    'name' => 'template4',
                    'params' => [
                    ],
                ],
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
            ],
        ],
        '04-block-nested-template' => [
            [
                'name' => 'template7',
                'is_inline' => false,
                'params' => [
                    'name' => 'template7',
                    'content' => 'content7<eztemplate name="template8"><ezcontent>content8</ezcontent></eztemplate>',
                    'params' => [
                    ],
                ],
            ],
            [
                'name' => 'template8',
                'is_inline' => false,
                'params' => [
                    'name' => 'template8',
                    'content' => 'content8',
                    'params' => [
                    ],
                ],
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
            ],
        ],
    ];
}
