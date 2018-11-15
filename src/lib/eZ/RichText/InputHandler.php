<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText;

use DOMDocument;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value;

class InputHandler implements InputHandlerInterface
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\ConverterDispatcher
     */
    private $converter;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\Normalizer
     */
    private $normalizer;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface
     */
    private $schemaValidator;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface
     */
    private $docbookValidator;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\RelationProcessor
     */
    private $relationProcessor;

    /**
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory $domDocumentFactory
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\ConverterDispatcher $inputConverter
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\Normalizer $inputNormalizer
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface $schemaValidator
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface $dockbookValidator
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\RelationProcessor $relationProcessor
     */
    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        ConverterDispatcher $inputConverter,
        Normalizer $inputNormalizer,
        ValidatorInterface $schemaValidator,
        ValidatorInterface $dockbookValidator,
        RelationProcessor $relationProcessor
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->converter = $inputConverter;
        $this->normalizer = $inputNormalizer;
        $this->schemaValidator = $schemaValidator;
        $this->docbookValidator = $dockbookValidator;
        $this->relationProcessor = $relationProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function fromString(?string $inputValue = null): DOMDocument
    {
        if (empty($inputValue)) {
            $inputValue = Value::EMPTY_VALUE;
        }

        if ($this->normalizer->accept($inputValue)) {
            $inputValue = $this->normalizer->normalize($inputValue);
        }

        return $this->fromDocument($this->domDocumentFactory->loadXMLString($inputValue));
    }

    /**
     * {@inheritdoc}
     */
    public function fromDocument(DOMDocument $inputValue): DOMDocument
    {
        $errors = $this->schemaValidator->validateDocument($inputValue);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                '$inputValue',
                'Validation of XML content failed: ' . implode("\n", $errors)
            );
        }

        return $this->converter->dispatch($inputValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(DOMDocument $document): array
    {
        return $this->relationProcessor->getRelations($document);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(DOMDocument $document): array
    {
        return $this->docbookValidator->validateDocument($document);
    }
}
