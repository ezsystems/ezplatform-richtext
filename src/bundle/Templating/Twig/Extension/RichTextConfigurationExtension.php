<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\Templating\Twig\Extension;

use EzSystems\EzPlatformRichText\API\Configuration;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension exposing RichText Configuration as ez_richtext_config global Twig variable.
 *
 * @internal To access configuration use \EzSystems\EzPlatformRichText\API\Configuration\ProviderService
 */
final class RichTextConfigurationExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var \EzSystems\EzPlatformRichText\API\Configuration\ProviderService */
    private $configurationProvider;

    public function __construct(Configuration\ProviderService $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function getName()
    {
        return 'ezpublish.rich_text';
    }

    public function getGlobals(): array
    {
        return [
            'ez_richtext_config' => $this->configurationProvider->getConfiguration(),
        ];
    }
}
