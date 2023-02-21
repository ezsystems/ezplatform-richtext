<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use eZ\Publish\API\Repository\Tests\SetupFactory\Legacy as CoreLegacySetupFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Used to setup the infrastructure for Repository Public API integration tests,
 * based on Repository with Legacy Storage Engine implementation.
 */
class LegacySetupFactory extends CoreLegacySetupFactory
{
    use RichTextSetupFactoryTrait;

    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        parent::externalBuildContainer($containerBuilder);

        $this->loadRichTextSettings($containerBuilder);
    }
}
