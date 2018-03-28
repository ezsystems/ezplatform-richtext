<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);
/**
 * This file contains the ValidatorDispatcher class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\EzPlatformRichTextFieldType\eZ\RichText;

use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use DOMDocument;

/**
 * Dispatcher for various validators depending on the XML document namespace.
 */
class ValidatorDispatcher
{
    /**
     * Mapping of namespaces to validators.
     *
     * @var \EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Validator[]
     */
    protected $mapping = [];

    /**
     * @param \EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Validator[] $validatorMap
     */
    public function __construct($validatorMap)
    {
        foreach ($validatorMap as $namespace => $validator) {
            $this->addValidator($namespace, $validator);
        }
    }

    /**
     * Adds validator mapping.
     *
     * @param string $namespace
     * @param \EzSystems\EzPlatformRichTextFieldType\eZ\RichText\Validator $validator
     */
    public function addValidator($namespace, Validator $validator = null)
    {
        $this->mapping[$namespace] = $validator;
    }

    /**
     * Dispatches DOMDocument to the namespace mapped validator.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     *
     * @param \DOMDocument $document
     *
     * @return string[]
     */
    public function dispatch(DOMDocument $document)
    {
        $documentNamespace = $document->documentElement->lookupNamespaceURI(null);
        // checking for null as ezxml has no default namespace...
        if ($documentNamespace === null) {
            $documentNamespace = $document->documentElement->lookupNamespaceURI('xhtml');
        }

        foreach ($this->mapping as $namespace => $validator) {
            if ($documentNamespace === $namespace) {
                if ($validator === null) {
                    return [];
                }

                return $validator->validate($document);
            }
        }

        throw new NotFoundException('Validator', $documentNamespace);
    }
}
