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

namespace ZEngine\Type;

use Closure;
use PHPUnit\Framework\TestCase;

class ClosureEntryTest extends TestCase
{
    private Closure $closure;

    protected function setUp(): void
    {
        $this->closure = function () {
            $self = isset($this) ? $this : null;

            return [
                'class' => $self ? get_class($self) : null,
                'scope' => get_called_class()
            ];
        };
    }

    public function testGetCalledScope(): void
    {
        if (PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION === 1) {
            $this->markTestSkipped();
        }

        $scope = (new ClosureEntry($this->closure))->getCalledScope();
        $this->assertSame(self::class, $scope);
        $result = ($this->closure)();
        $this->assertSame(get_class($this), $result['scope']);
    }

    /**
     * @group internal
     */
    public function testSetCalledScope(): void
    {
        $closureEntry = new ClosureEntry($this->closure);
        $closureEntry->setCalledScope(\Exception::class);

        $scope = $closureEntry->getCalledScope();

        $this->assertSame(\Exception::class, $scope);
        $result = ($this->closure)();
        $this->markTestIncomplete('This test does not update internal scope variable, or it is cached');
    }

    /**
     * @group internal
     */
    public function testSetThis(): void
    {
        if (PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION === 1) {
            $this->markTestSkipped();
        }

        $closureEntry = new ClosureEntry($this->closure);
        $closureEntry->setThis(new \Exception());

        $result = ($this->closure)();

        $this->assertSame(\Exception::class, $result['scope']);
        $this->assertSame(\Exception::class, $result['class']);
    }
}
