<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Handles compatibility with kernel 7.x.
 */
class KernelRichTextPass implements CompilerPassInterface
{
    private $servicesToRemove = [
        'ezpublish.fieldType.ezrichtext',
        'ezpublish.fieldType.ezrichtext.converter',
        'ezpublish.fieldType.indexable.ezrichtext',
        'ezpublish_rest.field_type_processor.ezrichtext',
        'EzSystems\\RepositoryForms\\FieldType\\Mapper\\RichTextFormMapper',
    ];

    public function process(ContainerBuilder $container)
    {
        array_map(
            function ($service) use ($container) {
                if ($container->hasDefinition($service)) {
                    $container->log($this, "Removed ezpublish-kernel richtext service: $service");
                    $container->removeDefinition($service);
                }
            },
            $this->servicesToRemove
        );
    }
}
