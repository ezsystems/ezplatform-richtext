<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Configuration\Provider;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRestBundle\Tests\Functional\TestCase;
use EzSystems\EzPlatformRichText\SPI\Configuration\Provider;

abstract class BaseProviderTestCase extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    public function setUp(): void
    {
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    abstract public function createProvider(): Provider;

    abstract public function getExpectedProviderName(): string;

    /**
     * @covers \EzSystems\EzPlatformRichText\SPI\Configuration\Provider::getName
     */
    final public function testGetName(): void
    {
        self::assertSame(
            $this->getExpectedProviderName(),
            $this->createProvider()->getName()
        );
    }
}
