<?php
/**
 * Z-Engine framework
 *
 * @copyright Copyright 2019, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 */
declare(strict_types=1);

namespace ZEngine\Reflection;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Test function to reflect
 */
function testFunction(): ?string
{
    return 'Test';
}

class ReflectionFunctionTest extends TestCase
{
    private ReflectionFunction $refFunction;

    protected function setUp(): void
    {
        $this->refFunction = new ReflectionFunction(__NAMESPACE__ . '\\' . 'testFunction');
    }

    public function testSetDeprecated(): void
    {
        $this->markTestSkipped('User function does not trigger deprecation error');
    }

    public function testSetInternalFunctionDeprecated(): void
    {
        $currentReporting = error_reporting();
        error_reporting(E_ALL);
        $refFunction = new ReflectionFunction('var_dump');
        $refFunction->setDeprecated();
        $this->assertTrue($refFunction->isDeprecated());
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->assertSame(E_DEPRECATED, $errno);
            $this->assertStringContainsString('Function var_dump() is deprecated', $errstr);

            return true;
        });
        ob_start();
        var_dump($currentReporting);
        ob_end_clean();
        error_reporting($currentReporting);
        $refFunction->setDeprecated(false);
        restore_error_handler();
    }

    #[Group('internal')]
    public function testRedefineThrowsAnExceptionForIncompatibleCallback(): void
    {
        $this->expectException(\ReflectionException::class);
        $expectedRegexp = '/"function \(\)" should be compatible with original "function \(\)\: \?string"/';
        $this->expectExceptionMessageMatches($expectedRegexp);

        $this->refFunction->redefine(function () {
            echo 'Nope';
        });
    }

    #[Group('internal')]
    public function testRedefine(): void
    {
        $this->refFunction->redefine(function (): ?string {
            return 'Yes';
        });
        // Check that all main info were preserved
        $this->assertFalse($this->refFunction->isClosure());
        $this->assertSame('testFunction', $this->refFunction->getShortName());

        $result = testFunction();

        // Our function now returns Yes instead of Test
        $this->assertSame('Yes', $result);
    }

    #[Group('internal')]
    public function testRedefineInternalFunc(): void
    {
        $originalValue = zend_version();
        $refFunction   = new ReflectionFunction('zend_version');

        $refFunction->redefine(function (): string {
            return 'Z-Engine';
        });

        $modifiedValue = zend_version();
        $this->assertNotSame($originalValue, $modifiedValue);
        $this->assertSame('Z-Engine', $modifiedValue);
    }
}
