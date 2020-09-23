<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\eZ\RichText;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;

/**
 * Symfony implementation of RichText field type embed renderer.
 */
class Renderer implements RendererInterface
{
    const RESOURCE_TYPE_CONTENT = 0;
    const RESOURCE_TYPE_LOCATION = 1;

    /**
     * @var \eZ\Publish\Core\Repository\Repository
     */
    protected $repository;

    /**
     * @deprecated since 2.1.2
     *
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var string
     */
    protected $tagConfigurationNamespace;

    /**
     * @var string
     */
    protected $styleConfigurationNamespace;

    /**
     * @var string
     */
    protected $embedConfigurationNamespace;

    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $templateEngine;

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /**
     * @var array
     */
    private $customTagsConfiguration;

    /**
     * @var array
     */
    private $customStylesConfiguration;

    /**
     * @param string $tagConfigurationNamespace
     * @param string $styleConfigurationNamespace
     * @param string $embedConfigurationNamespace
     */
    public function __construct(
        Repository $repository,
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigResolverInterface $configResolver,
        EngineInterface $templateEngine,
        $tagConfigurationNamespace,
        $styleConfigurationNamespace,
        $embedConfigurationNamespace,
        LoggerInterface $logger = null,
        array $customTagsConfiguration = [],
        array $customStylesConfiguration = []
    ) {
        $this->repository = $repository;
        $this->authorizationChecker = $authorizationChecker;
        $this->configResolver = $configResolver;
        $this->templateEngine = $templateEngine;
        $this->tagConfigurationNamespace = $tagConfigurationNamespace;
        $this->styleConfigurationNamespace = $styleConfigurationNamespace;
        $this->embedConfigurationNamespace = $embedConfigurationNamespace;
        $this->logger = $logger ?? new NullLogger();
        $this->customTagsConfiguration = $customTagsConfiguration;
        $this->customStylesConfiguration = $customStylesConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderStyle($name, array $parameters, $isInline)
    {
        return $this->renderTemplate($name, 'style', $parameters, $isInline);
    }

    /**
     * {@inheritdoc}
     */
    public function renderTag($name, array $parameters, $isInline)
    {
        return $this->renderTemplate($name, 'tag', $parameters, $isInline);
    }

    /**
     * {@inheritdoc}
     */
    public function renderContentEmbed($contentId, $viewType, array $parameters, $isInline)
    {
        try {
            /** @var \eZ\Publish\API\Repository\Values\Content\ContentInfo $contentInfo */
            $contentInfo = $this->repository->sudo(
                function (Repository $repository) use ($contentId) {
                    return $repository->getContentService()->loadContentInfo($contentId);
                }
            );
        } catch (NotFoundException $e) {
            $this->logger->error("Could not render embedded resource: Content #{$contentId} not found");

            return null;
        }

        return $this->renderEmbed($contentInfo, static::RESOURCE_TYPE_CONTENT, $isInline, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function renderLocationEmbed($locationId, $viewType, array $parameters, $isInline)
    {
        try {
            /** @var \eZ\Publish\API\Repository\Values\Content\Location $location */
            $location = $this->repository->sudo(
                function (Repository $repository) use ($locationId) {
                    return $repository->getLocationService()->loadLocation($locationId);
                }
            );
        } catch (NotFoundException $e) {
            $this->logger->error("Could not render embedded resource: Location #{$locationId} not found");

            return null;
        }
        if ($location->invisible) {
            $this->logger->error("Could not render embedded resource: Location #{$locationId} is not visible");

            return null;
        }

        return $this->renderEmbed(
            $location->getContentInfo(),
            static::RESOURCE_TYPE_LOCATION,
            $isInline,
            $parameters,
            $location
        );
    }

    protected function renderEmbed(
        ContentInfo $contentInfo,
        int $resourceType,
        bool $isInline,
        array $parameters,
        Location $location = null
    ): ?string {
        if (!$contentInfo->mainLocationId) {
            $this->logger->error("Could not render embedded resource: Content #{$contentInfo->id} is trashed.");

            return null;
        }
        if ($contentInfo->isHidden) {
            $this->logger->error("Could not render embedded resource: Content #{$contentInfo->id} is hidden.");

            return null;
        }

        $isDenied = false;
        try {
            $this->checkContentInfoPermissions($contentInfo, $location);
        } catch (AccessDeniedException $e) {
            $this->logger->error(
                "Could not render embedded resource: access denied to embed Content #{$contentInfo->id}"
            );
            $isDenied = true;
        }

        $templateName = $this->getEmbedTemplateName($resourceType, $isInline, $isDenied);
        if ($templateName === null) {
            $this->logger->error('Could not render embedded resource: no template configured');

            return null;
        }
        if (!$this->templateEngine->exists($templateName)) {
            $this->logger->error("Could not render embedded resource: template '{$templateName}' does not exists");

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    /**
     * Renders template tag.
     *
     * @param string $name
     * @param string $type
     * @param bool $isInline
     *
     * @return string
     */
    public function renderTemplate($name, $type, array $parameters, $isInline)
    {
        switch ($type) {
            case 'style':
                $templateName = $this->getStyleTemplateName($name, $isInline);
                break;
            case 'tag':
            default:
                $templateName = $this->getTagTemplateName($name, $isInline);
        }

        if ($templateName === null) {
            $this->logger->error(
                "Could not render template {$type} '{$name}': no template configured"
            );

            return null;
        }

        if (!$this->templateEngine->exists($templateName)) {
            $this->logger->error(
                "Could not render template {$type} '{$name}': template '{$templateName}' does not exist"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    /**
     * Renders template $templateReference with given $parameters.
     *
     * @param string $templateReference
     *
     * @return string
     */
    protected function render($templateReference, array $parameters)
    {
        return $this->templateEngine->render(
            $templateReference,
            $parameters
        );
    }

    /**
     * Returns configured template name for the given Custom Style identifier.
     *
     * @param string $identifier
     * @param bool $isInline
     *
     * @return string|null
     */
    protected function getStyleTemplateName($identifier, $isInline)
    {
        if (!empty($this->customStylesConfiguration[$identifier]['template'])) {
            return $this->customStylesConfiguration[$identifier]['template'];
        }

        $this->logger->warning(
            "Template style '{$identifier}' configuration was not found"
        );

        if ($isInline) {
            $configurationReference = $this->styleConfigurationNamespace . '.default_inline';
        } else {
            $configurationReference = $this->styleConfigurationNamespace . '.default';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Template style '{$identifier}' default configuration was not found"
        );

        return null;
    }

    /**
     * Returns configured template name for the given template tag identifier.
     *
     * @param string $identifier
     * @param bool $isInline
     *
     * @return string|null
     */
    protected function getTagTemplateName($identifier, $isInline)
    {
        if (isset($this->customTagsConfiguration[$identifier])) {
            return $this->customTagsConfiguration[$identifier]['template'];
        }

        // BC layer:
        $configurationReference = $this->tagConfigurationNamespace . '.' . $identifier;

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }
        // End of BC layer --/

        $this->logger->warning(
            "Template tag '{$identifier}' configuration was not found"
        );

        if ($isInline) {
            $configurationReference = $this->tagConfigurationNamespace . '.default_inline';
        } else {
            $configurationReference = $this->tagConfigurationNamespace . '.default';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Template tag '{$identifier}' default configuration was not found"
        );

        return null;
    }

    /**
     * Returns configured template reference for the given embed parameters.
     *
     * @param $resourceType
     * @param $isInline
     * @param $isDenied
     *
     * @return string|null
     */
    protected function getEmbedTemplateName($resourceType, $isInline, $isDenied)
    {
        $configurationReference = $this->embedConfigurationNamespace;

        if ($resourceType === static::RESOURCE_TYPE_CONTENT) {
            $configurationReference .= '.content';
        } else {
            $configurationReference .= '.location';
        }

        if ($isInline) {
            $configurationReference .= '_inline';
        }

        if ($isDenied) {
            $configurationReference .= '_denied';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Embed tag configuration '{$configurationReference}' was not found"
        );

        $configurationReference = $this->embedConfigurationNamespace;

        $configurationReference .= '.default';

        if ($isInline) {
            $configurationReference .= '_inline';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Embed tag default configuration '{$configurationReference}' was not found"
        );

        return null;
    }

    /**
     * Check embed permissions for the given Content $id.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @deprecated since 6.7
     *
     * @param int $contentId
     */
    protected function checkContent($contentId)
    {
        /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
        $content = $this->repository->sudo(
            function (Repository $repository) use ($contentId) {
                return $repository->getContentService()->loadContent($contentId);
            }
        );

        $this->checkContentInfoPermissions($content->contentInfo);
    }

    /**
     * Check embed permissions for the given Content Info.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function checkContentInfoPermissions(ContentInfo $contentInfo, Location $targetLocation = null): void
    {
        $permissionResolver = $this->repository->getPermissionResolver();

        $targets = $targetLocation ? [$targetLocation] : [];

        // Check both 'content/read' and 'content/view_embed'.
        if (
            !$permissionResolver->canUser('content', 'read', $contentInfo, $targets)
            && !$permissionResolver->canUser('content', 'view_embed', $contentInfo, $targets)
        ) {
            throw new AccessDeniedException();
        }

        // Check that Content is published, since sudo allows loading unpublished content.
        if (!$contentInfo->isPublished() && !$permissionResolver->canUser('content', 'versionread', $contentInfo)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * Checks embed permissions for the given Location $id and returns the Location.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @deprecated since 2.1.2
     *
     * @param int|string $id
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected function checkLocation($id)
    {
        /** @var \eZ\Publish\API\Repository\Values\Content\Location $location */
        $location = $this->repository->sudo(
            function (Repository $repository) use ($id) {
                return $repository->getLocationService()->loadLocation($id);
            }
        );
        $this->checkContentInfoPermissions($location->getContentInfo(), $location);

        return $location;
    }
}
