<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\LinkManager;

use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway;
use EzSystems\EzPlatformRichText\LinkManager\Link\Info;
use EzSystems\EzPlatformRichText\LinkManager\LinkManagerService;
use PHPUnit\Framework\TestCase;

class LinkManagerTest extends TestCase
{
    /** @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway|\PHPUnit\Framework\MockObject\MockObject */
    private $gateway;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\LinkManagerService */
    private $linkManager;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->linkManager = new LinkManagerService(
            $this->gateway
        );
    }

    /**
     * @dataProvider providerForAddLinks
     */
    public function testAddLinks(
        array $addToLinkManager,
        array $externalLinkToAdd,
        array $linksToInsert,
        array $existingLinksId,
        array $insertLinksId
    ) {
        $versionNo = 24;
        $fieldId = 42;

        $this->gateway
            ->method('getUrlIdMap')
            ->with($this->equalTo($externalLinkToAdd))
            ->willReturn($existingLinksId);

        $this->gateway
            ->expects($this->never())
            ->method('getIdUrlMap');

        $this->gateway
            ->expects($this->exactly(\count($linksToInsert)))
            ->method('insertUrl')
            ->withConsecutive($linksToInsert)
            ->willReturnOnConsecutiveCalls(...array_values($insertLinksId));

        $consecutiveCallParameters = [];
        foreach (array_merge($existingLinksId, $insertLinksId) as $idToLink) {
            $consecutiveCallParameters[] = [$idToLink, $fieldId, $versionNo];
        }

        $this->gateway
            ->expects($this->exactly(\count($consecutiveCallParameters)))
            ->method('linkUrl')
            ->withConsecutive(
                ...$consecutiveCallParameters
            );

        $versionInfo = new VersionInfo(['versionNo' => $versionNo]);
        $field = new Field(['id' => $fieldId]);

        $this->linkManager->addLinks(
            $versionInfo,
            $field,
            $addToLinkManager
        );
    }

    public function providerForAddLinks()
    {
        return [
            [
                'add_to_link_manager' => [
                    //Content link
                    new Info(
                        'abcdef789',
                        'fragment1',
                        false,
                    ),
                    //Existing external link
                    new Info(
                        'http://www.ez.no',
                        'fragment2',
                        true,
                    ),
                    //Existing external link repeated
                    new Info(
                        'http://www.ez.no',
                        'fragment2',
                        true,
                    ),
                    //New external link
                    new Info(
                        'http://share.ez.no',
                        'fragment3',
                        true,
                    ),
                    //New external link repeated
                    new Info(
                        'http://share.ez.no',
                        'fragment3',
                        true,
                    ),
                ],
                'external_links_to_add' => [
                    1 => 'http://www.ez.no',
                    2 => 'http://www.ez.no',
                    3 => 'http://share.ez.no',
                    4 => 'http://share.ez.no',
                ],
                'links_to_insert' => [
                    'http://share.ez.no',
                ],
                'existing_link_id' => [
                    'http://www.ez.no' => 123,
                ],
                'to_insert_link_id' => [
                    'http://share.ez.no' => 456,
                ],
            ],
        ];
    }
}
