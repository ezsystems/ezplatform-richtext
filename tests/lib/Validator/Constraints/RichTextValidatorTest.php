<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\Validator\Constraints;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\Exception\InvalidXmlException;
use EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface;
use EzSystems\EzPlatformRichText\Validator\Constraints\RichText;
use EzSystems\EzPlatformRichText\Validator\Constraints\RichTextValidator;
use LibXMLError;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RichTextValidatorTest extends TestCase
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $inputHandler;

    /**
     * @var \Symfony\Component\Validator\Context\ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $executionContext;

    /**
     * @var \EzSystems\EzPlatformRichText\Validator\Constraints\RichTextValidator
     */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->inputHandler = $this->createMock(InputHandlerInterface::class);
        $this->executionContext = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new RichTextValidator($this->inputHandler);
        $this->validator->initialize($this->executionContext);
    }

    public function testValidateInvalidXMLString(): void
    {
        $xml = 'THIS IS INVALID XML';

        $expectedErrors = [
            $this->createLibXMLError('This is not XML string: A'),
            $this->createLibXMLError('This is not XML string: B'),
        ];

        $this->inputHandler
            ->expects($this->once())
            ->method('fromString')
            ->with($xml)
            ->willThrowException($this->createInvalidXmlExceptionMock($expectedErrors));

        foreach ($expectedErrors as $i => $error) {
            $this->executionContext
                ->expects($this->at($i))
                ->method('addViolation')
                ->with($error->message);
        }

        $this->inputHandler
            ->expects($this->never())
            ->method('validate');

        $this->validator->validate($xml, new RichText());
    }

    public function testValidateNonXMLValue(): void
    {
        $object = new stdClass();

        $this->inputHandler
            ->expects($this->never())
            ->method('fromString');

        $this->inputHandler
            ->expects($this->never())
            ->method('validate');

        $this->executionContext
            ->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($object, new RichText());
    }

    public function testValidateDOMDocument(): void
    {
        $doc = $this->createMock(DOMDocument::class);

        $expectedErrors = [
            'This is not XML string: A',
            'This is not XML string: B',
        ];

        $this->inputHandler
            ->expects($this->never())
            ->method('fromString');

        $this->inputHandler
            ->expects($this->once())
            ->method('validate')
            ->with($doc)
            ->willReturn($expectedErrors);

        foreach ($expectedErrors as $i => $error) {
            $this->executionContext
                ->expects($this->at($i))
                ->method('addViolation')
                ->with($error);
        }

        $this->validator->validate($doc, new RichText());
    }

    private function createInvalidXmlExceptionMock(array $errors): InvalidXmlException
    {
        $ex = $this->createMock(InvalidXmlException::class);
        $ex->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        return $ex;
    }

    private function createLibXMLError(string $message): LibXMLError
    {
        $error = new LibXMLError();
        $error->message = $message;

        return $error;
    }
}
