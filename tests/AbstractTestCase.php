<?php

namespace Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

abstract class AbstractTestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();
    }
}
