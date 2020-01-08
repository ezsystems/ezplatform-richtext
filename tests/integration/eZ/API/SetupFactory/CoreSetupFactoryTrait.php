<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\IntegrationTests\EzPlatformRichText\eZ\API\SetupFactory;

use eZ\Publish\Core\Base\Container\Compiler;
use RuntimeException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

trait CoreSetupFactoryTrait
{
    /**
     * Load ezpublish-kernel settings and setup container.
     *
     * @todo refactor ezpublish-kernel SetupFactory to include that setup w/o relying on config.php
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     *
     * @throws \Exception
     */
    protected function loadCoreSettings(ContainerBuilder $containerBuilder)
    {
        // @todo refactor when refactoring kernel SetupFactory to avoid hardcoding package path
        $kernelRootDir = realpath(__DIR__ . '/../../../../../vendor/ezsystems/ezpublish-kernel');
        if (false === $kernelRootDir) {
            throw new RuntimeException('Unable to find the ezpublish-kernel package directory');
        }
        $settingsPath = "{$kernelRootDir}/eZ/Publish/Core/settings";

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));

        $loader->load('fieldtype_external_storages.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('fieldtypes.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('io.yml');
        $loader->load('repository.yml');
        $loader->load('repository/inner.yml');
        $loader->load('repository/event.yml');
        $loader->load('repository/siteaccessaware.yml');
        $loader->load('roles.yml');
        $loader->load('storage_engines/common.yml');
        $loader->load('storage_engines/cache.yml');
        $loader->load('storage_engines/legacy.yml');
        $loader->load('storage_engines/shortcuts.yml');
        $loader->load('search_engines/common.yml');
        $loader->load('settings.yml');
        $loader->load('thumbnails.yml');
        $loader->load('utils.yml');
        $loader->load('tests/common.yml');
        $loader->load('policies.yml');

        $loader->load('search_engines/legacy.yml');
        $loader->load('tests/integration_legacy.yml');

        // Cache settings (takes same env variables as ezplatform does, only supports "singleredis" setup)
        if (getenv('CUSTOM_CACHE_POOL') === 'singleredis') {
            /*
             * Symfony\Component\Cache\Adapter\RedisAdapter
             * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $redisClient
             * public function __construct($redisClient, $namespace = '', $defaultLifetime = 0)
             *
             * $redis = new \Redis();
             * $redis->connect('127.0.0.1', 6379, 2.5);
             */
            $containerBuilder
                ->register('ezpublish.cache_pool.driver.redis', 'Redis')
                ->addMethodCall('connect', [(getenv('CACHE_HOST') ?: '127.0.0.1'), 6379, 2.5]);

            $containerBuilder
                ->register('ezpublish.cache_pool.driver', RedisAdapter::class)
                ->setArguments([new Reference('ezpublish.cache_pool.driver.redis'), '', 120]);
        }

        $containerBuilder->setParameter('ezpublish.kernel.root_dir', realpath($kernelRootDir));

        $containerBuilder->addCompilerPass(new Compiler\FieldTypeRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Persistence\FieldTypeRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\RegisterLimitationTypePass());

        $containerBuilder->addCompilerPass(new Compiler\Storage\ExternalStorageRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\FieldValueConverterRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\RoleLimitationConverterPass());

        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriteriaConverterPass());
        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\SortClauseConverterPass());

        $containerBuilder->addCompilerPass(new Compiler\Search\FieldRegistryPass());

        $containerBuilder->setParameter(
            'legacy_dsn',
            self::$dsn
        );

        $containerBuilder->setParameter(
            'io_root_dir',
            self::$ioRootDir . '/'
        );

        // load overrides just before creating test Container
        $loader->load('tests/override.yml');
    }
}
