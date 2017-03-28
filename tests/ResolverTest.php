<?php

use PHPUnit\Framework\TestCase;
use Selmarinel\AllDifferentDirections\Resolver;

class ResolverTest extends TestCase
{
    public function test()
    {
        $resolver = new Resolver();
        $this->assertSame(1, $resolver->resolve());
    }
}
