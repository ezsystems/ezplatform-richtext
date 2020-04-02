<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\FieldType\RichText;

use eZ\Publish\SPI\FieldType\GatewayBasedStorage;
use eZ\Publish\SPI\FieldType\StorageGateway;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use eZ\Publish\SPI\Persistence\Content\Field;
use EzSystems\EzPlatformRichText\LinkManager\LinkManagerService;
use EzSystems\EzPlatformRichText\LinkManager\Transformer\RichTextLinkTransformer;
use EzSystems\EzPlatformRichText\LinkManager\Extractor\RichTextLinkExtractor;
use Psr\Log\LoggerInterface;
use DOMDocument;

class RichTextStorage extends GatewayBasedStorage
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway
     */
    protected $gateway;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\Extractor\RichTextLinkExtractor */
    private $linkExtractor;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\LinkManagerService */
    private $linkManager;

    /** @var \EzSystems\EzPlatformRichText\LinkManager\Transformer\RichTextLinkTransformer */
    private $linkTransformer;

    /**
     * @param \eZ\Publish\SPI\FieldType\StorageGateway $gateway
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        StorageGateway $gateway,
        RichTextLinkExtractor $linkExtractor,
        LinkManagerService $linkManager,
        RichTextLinkTransformer $replaceLink,
        LoggerInterface $logger = null
    ) {
        parent::__construct($gateway);
        $this->logger = $logger;
        $this->linkExtractor = $linkExtractor;
        $this->linkManager = $linkManager;
        $this->linkTransformer = $replaceLink;
    }

    /**
     * @see \eZ\Publish\SPI\FieldType\FieldStorage
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);
        $links = $this->linkExtractor->getLinksInDocument($document);

        if (empty($links)) {
            return false;
        }

        $linkInfoList = [];
        foreach ($links->getLinkDomElements() as $linkDOMElement) {
            $linkInfoList[] = $linkDOMElement->getLinkInfo();
        }

        $this->linkManager->addLinks(
            $versionInfo,
            $field,
            $linkInfoList
        );

        $documentWithReplacedLinks = $this->linkTransformer->atSave($links);

//        $this->gateway->unlinkUrl(
//            $field->id,
//            $versionInfo->versionNo,
//            array_values(
//                $urlIdMap
//            )
//        );

        $field->value->data = $documentWithReplacedLinks->saveXML();

        return true;
    }

    /**
     * Modifies $field if needed, using external data (like for Urls).
     *
     * @param \eZ\Publish\SPI\Persistence\Content\VersionInfo $versionInfo
     * @param \eZ\Publish\SPI\Persistence\Content\Field $field
     * @param array $context
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field, array $context)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);
        $links = $this->linkExtractor->getLinkInfoForRead($document);
        $documentWithReplacedLinks = $this->linkTransformer->atRead($links);

        $field->value->data = $documentWithReplacedLinks->saveXML();
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds, array $context)
    {
        foreach ($fieldIds as $fieldId) {
            $this->gateway->unlinkUrl($fieldId, $versionInfo->versionNo);
        }
    }

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData()
    {
        return true;
    }

    public function getIndexData(VersionInfo $versionInfo, Field $field, array $context)
    {
    }
}
