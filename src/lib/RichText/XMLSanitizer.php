<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use DOMText;
use DOMXPath;
use RuntimeException;

/**
 * @internal
 */
final class XMLSanitizer
{
    public function sanitizeXMLString(string $xmlString): string
    {
        $xmlString = $this->removeComments($xmlString);
        $xmlString = $this->removeDangerousTags($xmlString);
        $xmlString = $this->sanitizeDocType($xmlString);

        return $this->removeEmptyDocType($xmlString);
    }

    public function convertCDATAToText(DOMDocument $document): DOMDocument
    {
        $xpath = new DOMXPath($document);
        $cdataNodes = $xpath->query('//text()[ancestor-or-self::node()]');
        if ($cdataNodes === false) {
            return $document;
        }

        foreach ($cdataNodes as $cdataNode) {
            if ($cdataNode->nodeType === XML_CDATA_SECTION_NODE && $cdataNode->parentNode !== null) {
                $cdataNode->parentNode->replaceChild(new DOMText($cdataNode->textContent), $cdataNode);
            }
        }

        return $document;
    }

    private function removeComments(string $xmlString): string
    {
        $xmlString = preg_replace('/<!--\s?.*?\s?-->/s', '', $xmlString);

        if ($xmlString === null) {
            $this->throwRuntimeException(__METHOD__);
        }

        return $xmlString;
    }

    private function removeDangerousTags(string $xmlString): string
    {
        $xmlString = preg_replace('/<\s*(script|iframe|object|embed|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $xmlString);

        if ($xmlString === null) {
            $this->throwRuntimeException(__METHOD__);
        }

        return $xmlString;
    }

    private function sanitizeDocType(string $xmlString): string
    {
        $pattern = '/<\s*!DOCTYPE\s+(?<name>[^\s>]+)\s*(\[(?<entities>.*?)\]\s*)?>/is';

        if (!preg_match($pattern, $xmlString, $matches)) {
            return $xmlString;
        }

        $docTypeName = $matches['name'];
        $entitiesBlock = $matches['entities'] ?? '';
        [$safeEntities, $removedEntities] = $this->filterEntitiesFromDocType($entitiesBlock);

        foreach ($removedEntities as $entity) {
            $xmlString = preg_replace('/&' . preg_quote($entity, '/') . ';/i', '', $xmlString);

            if ($xmlString === null) {
                $this->throwRuntimeException(__METHOD__);
            }
        }

        $safeDocType = sprintf('<!DOCTYPE %s [ %s ]>', $docTypeName, implode("\n", $safeEntities));
        $xmlString = preg_replace($pattern, $safeDocType, $xmlString);

        if ($xmlString === null) {
            $this->throwRuntimeException(__METHOD__);
        }

        return $xmlString;
    }

    private function removeEmptyDocType(string $xmlString): string
    {
        $xmlString = preg_replace('/<\s*!DOCTYPE\s+[^\[\]>]*\[\s*\]>/is', '', $xmlString);

        if ($xmlString === null) {
            $this->throwRuntimeException(__METHOD__);
        }

        return $xmlString;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function filterEntitiesFromDocType(string $entitiesBlock): array
    {
        $lines = explode("\n", $entitiesBlock);
        $safeEntities = [];
        $entitiesToRemove = [];
        $entityDefinitions = [];

        foreach ($lines as $line) {
            $line = html_entity_decode($line, ENT_XML1, 'UTF-8');
            $line = trim($line);

            if (preg_match('/<!ENTITY\s+(\S+)\s+(SYSTEM|PUBLIC)\s+/i', $line, $matches)) {
                $entitiesToRemove[] = $matches[1];
                continue;
            }

            if (!preg_match('/<!ENTITY\s+(\S+)\s+"([^"]+)"/', $line, $matches)) {
                continue;
            }

            $entityName = $matches[1];
            $entityValue = $matches[2];
            $entityDefinitions[$entityName] = $entityValue;

            if (preg_match('/&\S+;/', $entityValue)) {
                $entitiesToRemove[] = $entityName;
                continue;
            }

            $safeEntities[] = $line;
        }

        $entitiesToRemove = $this->resolveRecursiveEntities($entityDefinitions, $entitiesToRemove);
        $safeEntities = array_filter($safeEntities, function ($line) use ($entitiesToRemove) {
            return !$this->containsUnsafeEntity($line, $entitiesToRemove);
        });

        return [$safeEntities, $entitiesToRemove];
    }

    /**
     * @param array<int, string> $entitiesToRemove
     * @param array<string, string> $entityDefinitions
     *
     * @return array<int, string>
     */
    private function resolveRecursiveEntities(array $entityDefinitions, array $entitiesToRemove): array
    {
        foreach ($entityDefinitions as $name => $value) {
            foreach ($entitiesToRemove as $toRemove) {
                if (strpos($value, "&$toRemove;") !== false && !in_array($name, $entitiesToRemove, true)) {
                    $entitiesToRemove[] = $name;
                }
            }
        }

        return array_unique($entitiesToRemove);
    }

    /**
     * @param array<int, string> $entitiesToRemove
     */
    private function containsUnsafeEntity(string $line, array $entitiesToRemove): bool
    {
        foreach ($entitiesToRemove as $toRemove) {
            if (strpos($line, $toRemove) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return never
     */
    private function throwRuntimeException(string $functionName): void
    {
        throw new RuntimeException(
            sprintf('%s returned null for "$xmlString", error: %s', $functionName, preg_last_error_msg())
        );
    }
}
