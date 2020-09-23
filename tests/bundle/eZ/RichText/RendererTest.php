<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\eZ\RichText;

use Exception;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Repository\Repository;
use EzSystems\EzPlatformRichTextBundle\eZ\RichText\Renderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;

class RendererTest extends TestCase
{
    public function setUp()
    {
        $this->repositoryMock = $this->getRepositoryMock();
        $this->authorizationCheckerMock = $this->getAuthorizationCheckerMock();
        $this->configResolverMock = $this->getConfigResolverMock();
        $this->templateEngineMock = $this->getTemplateEngineMock();
        $this->loggerMock = $this->getLoggerMock();
        parent::setUp();
    }

    public function testRenderTag(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $result = 'result';

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects($this->once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $parameters = ['parameters'];
        $isInline = true;

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn(null);

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render template tag '{$name}': no template configured");

        $this->assertNull(
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn('templateName');

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render template tag '{$name}': template '{$templateName}' does not exist");

        $this->assertNull(
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function providerForTestRenderTagWithTemplate(): array
    {
        return [
            [
                $tagName = 'tag1',
                [
                    ['hasParameter', $namespace = "test.name.space.tag.{$tagName}", true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName1']],
                ],
                [],
                $templateName,
                $templateName,
                false,
                'result',
            ],
            [
                $tagName = 'tag2',
                [
                    ['hasParameter', $namespace = "test.name.space.tag.{$tagName}", true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName2']],
                ],
                [],
                $templateName,
                $templateName,
                true,
                'result',
            ],
            [
                $tagName = 'tag3',
                [
                    ['hasParameter', "test.name.space.tag.{$tagName}", false],
                    ['hasParameter', $namespace = 'test.name.space.tag.default', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName3']],
                ],
                [
                    ['warning', "Template tag '{$tagName}' configuration was not found"],
                ],
                $templateName,
                $templateName,
                false,
                'result',
            ],
            [
                $tagName = 'tag4',
                [
                    ['hasParameter', "test.name.space.tag.{$tagName}", false],
                    ['hasParameter', $namespace = 'test.name.space.tag.default_inline', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName4']],
                ],
                [
                    ['warning', "Template tag '{$tagName}' configuration was not found"],
                ],
                $templateName,
                $templateName,
                true,
                'result',
            ],
            [
                $tagName = 'tag5',
                [
                    ['hasParameter', "test.name.space.tag.{$tagName}", false],
                    ['hasParameter', $namespace = 'test.name.space.tag.default', false],
                ],
                [
                    ['warning', "Template tag '{$tagName}' configuration was not found"],
                    ['warning', "Template tag '{$tagName}' default configuration was not found"],
                    ['error', "Could not render template tag '{$tagName}': no template configured"],
                ],
                null,
                null,
                false,
                null,
            ],
            [
                $tagName = 'tag6',
                [
                    ['hasParameter', "test.name.space.tag.{$tagName}", false],
                    ['hasParameter', $namespace = 'test.name.space.tag.default_inline', false],
                ],
                [
                    ['warning', "Template tag '{$tagName}' configuration was not found"],
                    ['warning', "Template tag '{$tagName}' default configuration was not found"],
                    ['error', "Could not render template tag '{$tagName}': no template configured"],
                ],
                null,
                null,
                true,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderTagWithTemplate
     */
    public function testRenderTagWithTemplate(
        $tagName,
        array $configResolverParams,
        array $loggerParams,
        $templateEngineTemplate,
        $renderTemplate,
        $isInline,
        $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render']);
        $parameters = ['parameters'];

        if (!isset($renderTemplate)) {
            $renderer
                ->expects($this->never())
                ->method('render');
        } else {
            $renderer
                ->expects($this->once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $this->templateEngineMock
                ->expects($this->once())
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($configResolverParams as $params) {
                $method = $params[0];
                $namespace = $params[1];
                $returnValue = $params[2];
                $this->configResolverMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($namespace)
                    ->willReturn($returnValue);
                ++$i;
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($loggerParams as $params) {
                $method = $params[0];
                $message = $params[1];
                $this->loggerMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($message);
                ++$i;
            }
        }

        $this->assertEquals(
            $renderResult,
            $renderer->renderTemplate($tagName, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderContentEmbed(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $result = 'result';
        $mainLocationId = 2;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($contentMock);

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = null;
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($contentMock);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions');

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedAccessDenied(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';
        $mainLocationId = 42;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($contentMock)
            ->will($this->throwException(new AccessDeniedException()));

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Content #{$contentId}");

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedTrashed(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentInfoPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->method('__get')
            ->will($this->returnValueMap([
                ['id', $contentId],
                ['mainLocationId', null],
            ]));

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentInfoMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is trashed.");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedHidden(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentInfoPermissions']);
        $contentId = 42;
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->getContentInfoMock($locationId, true);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentInfoMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is hidden.");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNotFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->throwException(new NotFoundException('Content', $contentId)));

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} not found");

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedThrowsException(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentInfoPermissions']);
        $contentId = 42;
        $mainLocationId = 2;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($contentMock)
            ->will($this->throwException(new Exception('Something threw up')));

        $this->expectExceptionMessage("Something threw up");
        $this->expectException(\Exception::class);
        $renderer->renderContentEmbed($contentId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderContentWithTemplate(): array
    {
        $contentId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content_denied', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName1']],
                ],
                [
                    ['error', "Could not render embedded resource: access denied to embed Content #{$contentId}"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content_inline_denied', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName2']],
                ],
                [
                    ['error', "Could not render embedded resource: access denied to embed Content #{$contentId}"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName3']],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content_inline', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName4']],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default', true],
                    ['getParameter', $namespace2, ['template' => $templateName = 'templateName5']],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content_inline', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default_inline', true],
                    ['getParameter', $namespace2, ['template' => $templateName = 'templateName6']],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default', false],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                    ['warning', "Embed tag default configuration '{$namespace2}' was not found"],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.content_inline', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default_inline', false],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                    ['warning', "Embed tag default configuration '{$namespace2}' was not found"],
                ],
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderContentWithTemplate
     */
    public function testRenderContentWithTemplate(
        $isInline,
        $deniedException,
        array $configResolverParams,
        array $loggerParams,
        $templateEngineTemplate,
        $renderTemplate,
        $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mainLocationId = 42;

        $contentMock = $this->getContentInfoMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        if (isset($deniedException)) {
            $renderer
                ->expects($this->once())
                ->method('checkContentInfoPermissions')
                ->with($contentMock)
                ->will($this->throwException($deniedException));
        } else {
            $renderer
                ->expects($this->once())
                ->method('checkContentInfoPermissions')
                ->with($contentMock);
        }

        if (!isset($renderTemplate)) {
            $renderer
                ->expects($this->never())
                ->method('render');
        } else {
            $renderer
                ->expects($this->once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $this->templateEngineMock
                ->expects($this->once())
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($configResolverParams as $params) {
                $method = $params[0];
                $namespace = $params[1];
                $returnValue = $params[2];
                $this->configResolverMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($namespace)
                    ->willReturn($returnValue);
                ++$i;
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($loggerParams as $params) {
                $method = $params[0];
                $message = $params[1];
                $this->loggerMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($message);
                ++$i;
            }
        }

        $this->assertEquals(
            $renderResult,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbed(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $result = 'result';
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $mockContentInfo = $this->getContentInfoMock($locationId);
        $mockLocation
            ->expects($this->once())
            ->method('getContentInfo')
            ->willReturn($mockContentInfo);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($mockContentInfo)
            ->willReturn(null);

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = null;
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $mockContentInfo = $this->getContentInfoMock($locationId);
        $mockLocation
            ->expects($this->once())
            ->method('getContentInfo')
            ->willReturn($mockContentInfo);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($mockContentInfo)
            ->willReturn(null);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $mockContentInfo = $this->getContentInfoMock($locationId);
        $mockLocation
            ->expects($this->once())
            ->method('getContentInfo')
            ->willReturn($mockContentInfo);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($mockContentInfo)
            ->willReturn(null);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedAccessDenied(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $locationId = 42;
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';

        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $mockContentInfo = $this->getContentInfoMock($locationId);
        $mockLocation
            ->expects($this->once())
            ->method('getContentInfo')
            ->willReturn($mockContentInfo);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->once())
            ->method('checkContentInfoPermissions')
            ->with($mockContentInfo)
            ->will($this->throwException(new AccessDeniedException()));

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Content #{$contentId}");

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedInvisible(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(true);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} is not visible");

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNotFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->throwException(new NotFoundException('Location', 42)));

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('exists');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} not found");

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedThrowsException(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentInfoPermissions']);
        $locationId = 42;

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->throwException(new Exception('Something threw up')));

        $this->expectExceptionMessage("Something threw up");
        $this->expectException(\Exception::class);
        $renderer->renderLocationEmbed($locationId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderLocationWithTemplate(): array
    {
        $contentId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location_denied', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName1']],
                ],
                [
                    ['error', "Could not render embedded resource: access denied to embed Content #{$contentId}"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location_inline_denied', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName2']],
                ],
                [
                    ['error', "Could not render embedded resource: access denied to embed Content #{$contentId}"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName3']],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location_inline', true],
                    ['getParameter', $namespace, ['template' => $templateName = 'templateName4']],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default', true],
                    ['getParameter', $namespace2, ['template' => $templateName = 'templateName5']],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location_inline', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default_inline', true],
                    ['getParameter', $namespace2, ['template' => $templateName = 'templateName6']],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default', false],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                    ['warning', "Embed tag default configuration '{$namespace2}' was not found"],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    ['hasParameter', $namespace = 'test.name.space.embed.location_inline', false],
                    ['hasParameter', $namespace2 = 'test.name.space.embed.default_inline', false],
                ],
                [
                    ['warning', "Embed tag configuration '{$namespace}' was not found"],
                    ['warning', "Embed tag default configuration '{$namespace2}' was not found"],
                ],
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderLocationWithTemplate
     */
    public function testRenderLocationWithTemplate(
        $isInline,
        $deniedException,
        array $configResolverParams,
        array $loggerParams,
        $templateEngineTemplate,
        $renderTemplate,
        $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render', 'checkContentInfoPermissions']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects($this->once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $mockContentInfo = $this->getContentInfoMock($locationId);
        $mockLocation
            ->expects($this->once())
            ->method('getContentInfo')
            ->willReturn($mockContentInfo);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($mockLocation);

        if (isset($deniedException)) {
            $renderer
                ->expects($this->once())
                ->method('checkContentInfoPermissions')
                ->with($mockContentInfo)
                ->will($this->throwException($deniedException));
        } else {
            $renderer
                ->expects($this->once())
                ->method('checkContentInfoPermissions')
                ->with($mockContentInfo)
                ->willReturn(null);
        }

        if (!isset($renderTemplate)) {
            $renderer
                ->expects($this->never())
                ->method('render');
        } else {
            $renderer
                ->expects($this->once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $this->templateEngineMock
                ->expects($this->once())
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($configResolverParams as $params) {
                $method = $params[0];
                $namespace = $params[1];
                $returnValue = $params[2];
                $this->configResolverMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($namespace)
                    ->willReturn($returnValue);
                ++$i;
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            $i = 0;
            foreach ($loggerParams as $params) {
                $method = $params[0];
                $message = $params[1];
                $this->loggerMock
                    ->expects($this->at($i))
                    ->method($method)
                    ->with($message);
                ++$i;
            }
        }

        $this->assertEquals(
            $renderResult,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    /**
     * @param array $methods
     *
     * @return \EzSystems\EzPlatformRichText\eZ\RichText\Renderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockedRenderer(array $methods = [])
    {
        return $this->getMockBuilder(Renderer::class)
            ->setConstructorArgs(
                [
                    $this->repositoryMock,
                    $this->authorizationCheckerMock,
                    $this->configResolverMock,
                    $this->templateEngineMock,
                    'test.name.space.tag',
                    'test.name.space.style',
                    'test.name.space.embed',
                    $this->loggerMock,
                ]
            )
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @var \eZ\Publish\API\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repositoryMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock(): MockObject
    {
        return $this->createMock(Repository::class);
    }

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $authorizationCheckerMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getAuthorizationCheckerMock(): MockObject
    {
        return $this->createMock(AuthorizationCheckerInterface::class);
    }

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configResolverMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigResolverMock(): MockObject
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    /**
     * @var \Symfony\Component\Templating\EngineInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $templateEngineMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTemplateEngineMock(): MockObject
    {
        return $this->createMock(EngineInterface::class);
    }

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMock(): MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }

    protected function getContentInfoMock($mainLocationId, $isHidden = false): MockObject
    {
        $contentId = $mainLocationId ?? null;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->method('__get')
            ->will($this->returnValueMap([
                ['id', $contentId],
                ['mainLocationId', $mainLocationId],
                ['isHidden', $isHidden],
            ]));

        return $contentInfoMock;
    }
}
