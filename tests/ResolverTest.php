<?php

use PHPUnit\Framework\TestCase;
use Selmarinel\AllDifferentDirections\Resolver;

class ResolverTest extends TestCase
{
    public function test()
    {
        $input = '3
87.342 34.30 start 0 walk 10.0
2.6762 75.2811 start -45.0 walk 40 turn 40.0 walk 60
58.518 93.508 start 270 walk 50 turn 90 walk 40 turn 13 walk 5
2
30 40 start 90 walk 5
40 50 start 180 walk 10 turn 90 walk 5
0';

        $output = "97.15 40.23 7.63 
30.00 45.00 0.00 
";

        $resolver = new Resolver();

        $this->assertSame($output, $resolver->resolve($input));
    }
}
