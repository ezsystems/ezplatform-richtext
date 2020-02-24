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
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLink;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkIterator;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\Attribute\LinkAttribute;
use EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface;
use EzSystems\EzPlatformRichText\eZ\RichText\TemplateInstance\TemplateIterator;
use Psr\Log\LoggerInterface;
use DOMDocument;
use DOMXPath;

class RichTextStorage extends GatewayBasedStorage
{
    private const EMPTY_HREF = '#';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\RichTextStorage\Gateway
     */
    protected $gateway;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface */
    protected $templateRegistry;

    /**
     * @param \eZ\Publish\SPI\FieldType\StorageGateway $gateway
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\Template\TemplateRegistryInterface $templateRegistry
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        StorageGateway $gateway,
        TemplateRegistryInterface $templateRegistry,
        LoggerInterface $logger = null
    ) {
        parent::__construct($gateway);

        $this->templateRegistry = $templateRegistry;
        $this->logger = $logger;
    }

    /**
     * @see \eZ\Publish\SPI\FieldType\FieldStorage
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);

        $this->extractURLsFromLinks($versionInfo, $field, $context, $document);

        $field->value->data = $document->saveXML();

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

        $urlIdSet = [];

        $iterator = new InternalLinkIterator($document, ['link', 'ezlink'], [InternalLink::EZURL_SCHEME]);

        $containsLinks = false;
        foreach ($iterator as $link) {
            $containsLinks = true;
            if (!empty($link->getId())) {
                $urlIdSet[$link->getId()] = true;
            }
        }

        $linkTemplateParamsById = [];
        foreach ($this->getTemplateLinkAttributeIterator($document) as $param) {
            $containsLinks = true;

            $link = InternalLink::fromString($param->getNode(), $param->getValue());
            if (!empty($link->getId())) {
                $id = $link->getId();

                $urlIdSet[$id] = true;
                if (!isset($linkTemplateParamsById[$id])) {
                    $linkTemplateParamsById[$id] = [];
                }

                $linkTemplateParamsById[$id][] = $link;
            }
        }

        if (!$containsLinks) {
            return;
        }

        $urlMap = $this->gateway->getIdUrlMap(array_keys($urlIdSet));

        foreach ($iterator as $link) {
            $id = $link->getId();

            if (isset($urlMap[$id])) {
                $href = $urlMap[$id] . $link->getFragment();
            } else {
                // URL id is empty or not in the DB
                if (isset($this->logger)) {
                    $this->logger->error("URL with ID {$id} not found");
                }

                $href = self::EMPTY_HREF;
            }

            $link->getNode()->setAttribute('xlink:href', $href);
        }

        foreach ($linkTemplateParamsById as $id => $links) {
            if (isset($urlMap[$id])) {
                $href = $urlMap[$id] . $link->getFragment();
            } else {
                // URL id is empty or not in the DB
                if (isset($this->logger)) {
                    $this->logger->error("URL with ID {$id} not found");
                }

                $href = self::EMPTY_HREF;
            }

            foreach ($links as $link) {
                $link->getNode()->textContent = $href;
            }
        }

        $field->value->data = $document->saveXML();
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

    private function extractURLsFromLinks(VersionInfo $versionInfo, Field $field, array $context, DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        // This will select only links with non-empty 'xlink:href' attribute value
        $xpathExpression = "//docbook:link[string( @xlink:href ) and not( starts-with( @xlink:href, 'ezurl://' )" .
            "or starts-with( @xlink:href, 'ezcontent://' )" .
            "or starts-with( @xlink:href, 'ezlocation://' )" .
            "or starts-with( @xlink:href, '#' ) )]";

        $links = $xpath->query($xpathExpression);

        if (empty($links)) {
            return;
        }

        $urlSet = [];
        $remoteIdSet = [];
        $linksInfo = [];

        /** @var \DOMElement $link */
        foreach ($links as $index => $link) {
            preg_match(
                '~^(ezremote://)?([^#]*)?(#.*|\\s*)?$~',
                $link->getAttribute('xlink:href'),
                $matches
            );
            $linksInfo[$index] = $matches;

            if (empty($matches[1])) {
                $urlSet[$matches[2]] = true;
            } else {
                $remoteIdSet[$matches[2]] = true;
            }
        }

        $linkTemplateParamsByUrl = [];
        foreach ($this->getTemplateLinkAttributeIterator($document) as $param) {
            $value = $param->getValue();
            if (!empty($value)) {
                $urlSet[$value] = true;
            }

            if (!isset($linkTemplateParamsByUrl[$value])) {
                $linkTemplateParamsByUrl[$value] = [];
            }

            $linkTemplateParamsByUrl[$value][] = $param;
        }

        $urlIdMap = $this->gateway->getUrlIdMap(array_keys($urlSet));
        $contentIds = $this->gateway->getContentIds(array_keys($remoteIdSet));
        $urlLinkSet = [];

        foreach ($links as $index => $link) {
            list(, $scheme, $url, $fragment) = $linksInfo[$index];

            if (empty($scheme)) {
                // Insert the same URL only once
                if (!isset($urlIdMap[$url])) {
                    $urlIdMap[$url] = $this->gateway->insertUrl($url);
                }
                // Link the same URL only once
                if (!isset($urlLinkSet[$url])) {
                    $this->gateway->linkUrl(
                        $urlIdMap[$url],
                        $field->id,
                        $versionInfo->versionNo
                    );
                    $urlLinkSet[$url] = true;
                }
                $href = "ezurl://{$urlIdMap[$url]}{$fragment}";
            } else {
                if (!isset($contentIds[$url])) {
                    throw new NotFoundException('Content', $url);
                }
                $href = "ezcontent://{$contentIds[$url]}{$fragment}";
            }

            $link->setAttribute('xlink:href', $href);
        }

        foreach ($linkTemplateParamsByUrl as $url => $params) {
            // Insert the same URL only once
            if (!isset($urlIdMap[$url])) {
                $urlIdMap[$url] = $this->gateway->insertUrl($url);
            }

            // Link the same URL only once
            if (!isset($urlLinkSet[$url])) {
                $this->gateway->linkUrl(
                    $urlIdMap[$url],
                    $field->id,
                    $versionInfo->versionNo
                );
                $urlLinkSet[$url] = true;
            }

            foreach ($params as $param) {
                /** @var \DOMElement $node */
                $node = $param->getNode();
                $node->textContent = "ezurl://{$urlIdMap[$url]}";
            }
        }
    }

    private function getTemplateLinkAttributeIterator(DOMDocument $document): iterable
    {
        foreach (new TemplateIterator($document) as $template) {
            if (!$this->templateRegistry->has($template->getName())) {
                continue;
            }

            $definition = $this->templateRegistry->get($template->getName());
            foreach ($definition->getAttributesOfType(LinkAttribute::class) as $attribute) {
                if ($template->hasParam($attribute->getName())) {
                    yield $template->getParam($attribute->getName());
                }
            }
        }
    }
}
