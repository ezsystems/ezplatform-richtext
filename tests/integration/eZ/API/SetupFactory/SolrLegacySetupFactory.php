<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use EzSystems\EzPlatformSolrSearchEngine\Tests\SetupFactory\LegacySetupFactory as BaseSolrLegacySetupFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Setup Factory for Solr integration w/ Legacy database and RichText package.
 */
class SolrLegacySetupFactory extends BaseSolrLegacySetupFactory
{
    use RichTextSetupFactoryTrait;

    protected function externalBuildContainer(ContainerBuilder $containerBuilder): void
    {
        parent::externalBuildContainer($containerBuilder);

        $this->loadRichTextSettings($containerBuilder);
    }
}
