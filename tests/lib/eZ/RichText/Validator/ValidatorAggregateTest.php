<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformRichText\eZ\RichText\Validator;

use DOMDocument;
use EzSystems\EzPlatformRichText\eZ\RichText\Validator\ValidatorAggregate;
use EzSystems\EzPlatformRichText\eZ\RichText\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ValidatorAggregateTest extends TestCase
{
    /**
     * @covers \EzSystems\EzPlatformRichText\eZ\RichText\ValidatorAggregate::validateDocument
     */
    public function testValidateDocument(): void
    {
        $doc = $this->createMock(DOMDocument::class);

        $expectedErrors = [];
        $validators = [];

        for ($i = 0; $i < 3; ++$i) {
            $errorMessage = "Validation error $i";

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->expects($this->once())
                ->method('validateDocument')
                ->with($doc)
                ->willReturn([$errorMessage]);

            $expectedErrors[] = $errorMessage;
            $validators[] = $validator;
        }

        $aggregate = new ValidatorAggregate($validators);
        $actualErrors = $aggregate->validateDocument($doc);

        $this->assertEquals($expectedErrors, $actualErrors);
    }
}
