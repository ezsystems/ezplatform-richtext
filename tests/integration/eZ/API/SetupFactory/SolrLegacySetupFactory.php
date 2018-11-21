<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use eZ\Publish\Core\Base\ServiceContainer;
use EzSystems\EzPlatformSolrSearchEngine\Tests\SetupFactory\LegacySetupFactory as BaseSolrLegacySetupFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Setup Factory for Solr integration w/ Legacy database and RichText package.
 */
class SolrLegacySetupFactory extends BaseSolrLegacySetupFactory
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

            $this->loadCoreSettings($containerBuilder);
            $this->loadRichTextSettings($containerBuilder);

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
}
