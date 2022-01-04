<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichTextBundle\eZ\RichText;

use eZ\Publish\Core\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformRichTextBundle\eZ\RichText\Renderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class RendererTest extends TestCase
{
    public function setUp(): void
    {
        $this->repositoryMock = $this->getRepositoryMock();
        $this->authorizationCheckerMock = $this->getAuthorizationCheckerMock();
        $this->configResolverMock = $this->getConfigResolverMock();
        $this->templateEngineMock = $this->getTemplateEngineMock();
        $this->loggerMock = $this->getLoggerMock();
        $this->loaderMock = $this->getLoaderMock();
        parent::setUp();
    }

    public function testRenderTag()
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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateConfigured()
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
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render template tag '{$name}': no template configured");

        $this->assertNull(
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateFound()
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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

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
                    [
                        [[$namespace = "test.name.space.tag.{$tagName}"]],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
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
                    [
                        [[$namespace = "test.name.space.tag.{$tagName}"]],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
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
                    [
                        [["test.name.space.tag.{$tagName}"], [$namespace = 'test.name.space.tag.default']],
                        [false, true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                    ],
                    [],
                ],
                $templateName,
                $templateName,
                false,
                'result',
            ],
            [
                $tagName = 'tag4',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], [$namespace = 'test.name.space.tag.default_inline']],
                        [false, true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                    ],
                    [],
                ],
                $templateName,
                $templateName,
                true,
                'result',
            ],
            [
                $tagName = 'tag5',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], ['test.name.space.tag.default']],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                        ["Template tag '{$tagName}' default configuration was not found"],
                    ],
                    [
                        ["Could not render template tag '{$tagName}': no template configured"],
                    ],
                ],
                null,
                null,
                false,
                null,
            ],
            [
                $tagName = 'tag6',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], ['test.name.space.tag.default_inline']],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                        ["Template tag '{$tagName}' default configuration was not found"],
                    ],
                    [
                        ["Could not render template tag '{$tagName}': no template configured"],
                    ],
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
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects($this->once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
            }
        }

        $this->assertEquals(
            $renderResult,
            $renderer->renderTemplate($tagName, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderContentEmbed()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $result = 'result';
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->willReturn($contentMock);

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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateConfigured()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = null;
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->willReturn($contentMock);

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
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateFound()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
            ->with($contentMock);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedAccessDenied()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Content #{$contentId}");

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedTrashed()
    {
        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects($this->once())
            ->method('__get')
            ->with('mainLocationId')
            ->willReturn(null);

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->expects($this->once())
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is trashed.");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedHidden()
    {
        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(
                ['mainLocationId'],
                ['isHidden'],
            )->willReturnOnConsecutiveCalls(
                2,
                true
            );

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is hidden.");

        $this->assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function providerForTestRenderContentEmbedNotFound()
    {
        return [
            [new NotFoundException('Content', 42)],
            [new NotFoundHttpException()],
        ];
    }

    /**
     * @dataProvider providerForTestRenderContentEmbedNotFound
     */
    public function testRenderContentEmbedNotFound(Exception $exception)
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->will($this->throwException($exception));

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} not found");

        $this->assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something threw up');

        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects($this->once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->will($this->throwException(new Exception('Something threw up')));

        $renderer->renderContentEmbed($contentId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderContentWithTemplate()
    {
        $contentId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.content_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
                ],
                [
                    [],
                    [
                        ["Could not render embedded resource: access denied to embed Content #{$contentId}"],
                    ],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.content_inline_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
                ],
                [
                    [],
                    [
                        ["Could not render embedded resource: access denied to embed Content #{$contentId}"],
                    ],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.content']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
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
                    [
                        [[$namespace = 'test.name.space.embed.content_inline']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
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
                    [
                        [
                            [$namespace = 'test.name.space.embed.content'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName5']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName6']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
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
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($contentMock);

        if (isset($deniedException)) {
            $renderer
                ->expects($this->once())
                ->method('checkContentPermissions')
                ->with($contentMock)
                ->will($this->throwException($deniedException));
        } else {
            $renderer
                ->expects($this->once())
                ->method('checkContentPermissions')
                ->with($contentMock)
                ->willReturn($contentMock);
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
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects($this->once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
            }
        }

        $this->assertEquals(
            $renderResult,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbed()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
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

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateConfigured()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
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

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects($this->never())
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateFound()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
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

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedAccessDenied()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
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

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects($this->once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Location #{$locationId}");

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedInvisible()
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
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

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} is not visible");

        $this->assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function providerForTestRenderLocationEmbedNotFound()
    {
        return [
            [new NotFoundException('Location', 42)],
            [new NotFoundHttpException()],
        ];
    }

    /**
     * @dataProvider providerForTestRenderLocationEmbedNotFound
     */
    public function testRenderLocationEmbedNotFound(Exception $exception)
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->will($this->throwException($exception));

        $renderer
            ->expects($this->never())
            ->method('render');

        $renderer
            ->expects($this->never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects($this->never())
            ->method('getLoader');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} not found");

        $this->assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something threw up');

        $renderer = $this->getMockedRenderer(['checkLocation']);
        $locationId = 42;

        $renderer
            ->expects($this->once())
            ->method('checkLocation')
            ->with($locationId)
            ->will($this->throwException(new Exception('Something threw up')));

        $renderer->renderLocationEmbed($locationId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderLocationWithTemplate()
    {
        $locationId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.location_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
                ],
                [
                    [],
                    [["Could not render embedded resource: access denied to embed Location #{$locationId}"]],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.location_inline_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
                ],
                [
                    [],
                    [["Could not render embedded resource: access denied to embed Location #{$locationId}"]],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.location']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
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
                    [
                        [[$namespace = 'test.name.space.embed.location_inline']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
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
                    [
                        [
                            [$namespace = 'test.name.space.embed.location'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName5']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName6']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
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
    ) {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mockLocation = $this->createMock(Location::class);

        if (isset($deniedException)) {
            $renderer
                ->expects($this->once())
                ->method('checkLocation')
                ->with($locationId)
                ->will($this->throwException($deniedException));
        } else {
            $mockLocation
                ->expects($this->once())
                ->method('__get')
                ->with('invisible')
                ->willReturn(false);

            $renderer
                ->expects($this->once())
                ->method('checkLocation')
                ->with($locationId)
                ->willReturn($mockLocation);
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
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects($this->once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects($this->exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects($this->never())
                ->method($this->anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects($this->exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
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
    protected function getRepositoryMock()
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
    protected function getAuthorizationCheckerMock()
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
    protected function getConfigResolverMock()
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
    protected function getTemplateEngineMock()
    {
        return $this->createMock(Environment::class);
    }

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @var \Twig\Loader\LoaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loaderMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoaderMock()
    {
        return $this->createMock(LoaderInterface::class);
    }

    protected function getContentMock($mainLocationId)
    {
        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(
                ['mainLocationId'],
                ['isHidden'],
            )->willReturnOnConsecutiveCalls(
                $mainLocationId,
                false
            );

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        return $contentMock;
    }
}
