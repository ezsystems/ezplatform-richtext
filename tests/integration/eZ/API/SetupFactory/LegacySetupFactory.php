<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use eZ\Publish\API\Repository\Tests\SetupFactory\Legacy as CoreLegacySetupFactory;
use eZ\Publish\Core\Base\ServiceContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Used to setup the infrastructure for Repository Public API integration tests,
 * based on Repository with Legacy Storage Engine implementation.
 */
class LegacySetupFactory extends CoreLegacySetupFactory
{
    use CoreSetupFactoryTrait;
    use RichTextSetupFactoryTrait;

    /**
     * Returns the service container used for initialization of the repository.
     *
     * @return \eZ\Publish\Core\Base\ServiceContainer
     *
     * @throws \Exception
     */
    public function getServiceContainer()
    {
        if (!isset(self::$serviceContainer)) {
            /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder */
            $containerBuilder = new ContainerBuilder();

            $this->externalBuildContainer($containerBuilder);

            self::$serviceContainer = new ServiceContainer(
                $containerBuilder,
                __DIR__,
                'var/cache',
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     *
     * @throws \Exception
     */
    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        parent::externalBuildContainer($containerBuilder);

        $this->loadCoreSettings($containerBuilder);
        $this->loadRichTextSettings($containerBuilder);
    }
}
