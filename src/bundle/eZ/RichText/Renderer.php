<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichTextBundle\eZ\RichText;

use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\RendererInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Symfony implementation of RichText field type embed renderer.
 */
class Renderer implements RendererInterface
{
    const RESOURCE_TYPE_CONTENT = 0;
    const RESOURCE_TYPE_LOCATION = 1;

    /** @var \eZ\Publish\Core\Repository\Repository */
    protected $repository;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Twig\Environment */
    protected $templateEngine;

    /** @var \eZ\Publish\API\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var string */
    protected $tagConfigurationNamespace;

    /** @var string */
    protected $styleConfigurationNamespace;

    /** @var string */
    protected $embedConfigurationNamespace;

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /** @var array */
    private $customTagsConfiguration;

    /** @var array */
    private $customStylesConfiguration;

    public function __construct(
        Repository $repository,
        ConfigResolverInterface $configResolver,
        Environment $templateEngine,
        PermissionResolver $permissionResolver,
        string $tagConfigurationNamespace,
        string $styleConfigurationNamespace,
        string $embedConfigurationNamespace,
        LoggerInterface $logger = null,
        array $customTagsConfiguration = [],
        array $customStylesConfiguration = []
    ) {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->templateEngine = $templateEngine;
        $this->permissionResolver = $permissionResolver;
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
    public function renderContentEmbed($contentId, $viewType, array $parameters, $isInline)
    {
        $isDenied = false;

        try {
            /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
            $content = $this->repository->sudo(
                function (Repository $repository) use ($contentId) {
                    return $repository->getContentService()->loadContent((int)$contentId);
                }
            );

            if (!$content->contentInfo->mainLocationId) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} is trashed."
                );

                return null;
            }

            if ($content->contentInfo->isHidden) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} is hidden."
                );

                return null;
            }

            $this->checkContentPermissions($content);
        } catch (AccessDeniedException $e) {
            $this->logger->error(
                "Could not render embedded resource: access denied to embed Content #{$contentId}"
            );

            $isDenied = true;
        } catch (Exception $e) {
            if ($e instanceof NotFoundHttpException || $e instanceof NotFoundException) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} not found"
                );

                return null;
            } else {
                throw $e;
            }
        }

        $templateName = $this->getEmbedTemplateName(
            static::RESOURCE_TYPE_CONTENT,
            $isInline,
            $isDenied
        );

        if ($templateName === null) {
            $this->logger->error(
                'Could not render embedded resource: no template configured'
            );

            return null;
        }

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
            $this->logger->error(
                "Could not render embedded resource: template '{$templateName}' does not exists"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function renderLocationEmbed($locationId, $viewType, array $parameters, $isInline)
    {
        $isDenied = false;

        try {
            $location = $this->checkLocation($locationId);

            if ($location->invisible) {
                $this->logger->error(
                    "Could not render embedded resource: Location #{$locationId} is not visible"
                );

                return null;
            }
        } catch (AccessDeniedException $e) {
            $this->logger->error(
                "Could not render embedded resource: access denied to embed Location #{$locationId}"
            );

            $isDenied = true;
        } catch (Exception $e) {
            if ($e instanceof NotFoundHttpException || $e instanceof NotFoundException) {
                $this->logger->error(
                    "Could not render embedded resource: Location #{$locationId} not found"
                );

                return null;
            } else {
                throw $e;
            }
        }

        $templateName = $this->getEmbedTemplateName(
            static::RESOURCE_TYPE_LOCATION,
            $isInline,
            $isDenied
        );

        if ($templateName === null) {
            $this->logger->error(
                'Could not render embedded resource: no template configured'
            );

            return null;
        }

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
            $this->logger->error(
                "Could not render embedded resource: template '{$templateName}' does not exists"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    /**
     * Renders template tag.
     *
     * @param string $name
     * @param string $type
     * @param array $parameters
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

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
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
     * @param array $parameters
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
     * Check embed permissions for the given Content.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    protected function checkContentPermissions(Content $content): void
    {
        // Check both 'content/read' and 'content/view_embed'.
        if (
            !$this->permissionResolver->canUser('content', 'read', $content)
            && !$this->permissionResolver->canUser('content', 'view_embed', $content)
        ) {
            throw new AccessDeniedException();
        }

        // Check that Content is published, since sudo allows loading unpublished content.
        if (
            !$content->getVersionInfo()->isPublished()
            && !$this->permissionResolver->canUser('content', 'versionread', $content)
        ) {
            throw new AccessDeniedException();
        }
    }

    /**
     * Checks embed permissions for the given Location $id and returns the Location.
     *
     * @param int|string $id
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    protected function checkLocation($id)
    {
        /** @var \eZ\Publish\API\Repository\Values\Content\Location $location */
        $location = $this->repository->sudo(
            function (Repository $repository) use ($id) {
                return $repository->getLocationService()->loadLocation($id);
            }
        );

        // Check both 'content/read' and 'content/view_embed'.
        if (
            !$this->permissionResolver->canUser(
                'content',
                'read',
                $location->contentInfo,
                [$location]
            )
            && !$this->permissionResolver->canUser(
                'content',
                'view_embed',
                $location->contentInfo,
                [$location]
            )
        ) {
            throw new AccessDeniedException();
        }

        return $location;
    }
}
