<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextFieldTypeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

        $def = $container->getDefinition('ezpublish.persistence.legacy.field_value_converter.registry');
        $methodCalls = [];
        foreach ($def->getMethodCalls() as $methodCall) {
            if ($methodCall[0] != 'register') {
                $methodCalls[] = $methodCall;
                continue;
            }

            if ($methodCall[1][0] != 'ezrichtext') {
                $methodCalls[] = $methodCall;
                continue;
            }

            if (!$methodCall[1][1] instanceof Reference || (string)$methodCall[1][1] !== 'ezpublish.fieldType.ezrichtext.converter') {
                $methodCalls[] = $methodCall;
                continue;
            }
        }
        $def->setMethodCalls($methodCalls);
    }
}
