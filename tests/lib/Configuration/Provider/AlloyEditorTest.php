<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration\Provider;

use EzSystems\EzPlatformRichText\Configuration\Provider\AlloyEditor;
use EzSystems\EzPlatformRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

class AlloyEditorTest extends BaseProviderTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\EzSystems\EzPlatformRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper */
    private $mapper;

    public function setUp(): void
    {
        parent::setUp();

        $this->mapper = $this->createMock(OnlineEditorConfigMapper::class);
    }

    public function createProvider(): Provider
    {
        return new AlloyEditor(
            [
                'extra_plugins' => ['plugin1', 'plugin2'],
                'extra_buttons' => ['button1', 'button2'],
            ],
            $this->configResolver,
            $this->mapper
        );
    }

    public function getExpectedProviderName(): string
    {
        return 'alloyEditor';
    }

    /**
     * @covers \EzSystems\Tests\EzPlatformRichText\Configuration\Provider\AlloyEditorTest::createProvider
     */
    public function testGetConfiguration(): void
    {
        $provider = $this->createProvider();

        $this->configResolver
            ->expects($this->any())
            ->method('hasParameter')
            ->willReturn(false);

        $this->mapper
            ->expects($this->once())
            ->method('mapCssClassesConfiguration')
            ->with([])
            ->willReturn(['class1', 'class2']);

        $this->mapper
            ->expects($this->once())
            ->method('mapDataAttributesConfiguration')
            ->with([])
            ->willReturn(['attr1', 'attr2']);

        self::assertEquals(
            [
                'extraPlugins' => ['plugin1', 'plugin2'],
                'extraButtons' => ['button1', 'button2'],
                'classes' => ['class1', 'class2'],
                'attributes' => ['attr1', 'attr2'],
            ],
            $provider->getConfiguration()
        );
    }
}
