<?php

namespace Tests;

use PHPUnit_Framework_TestCase;
use Mockery;

abstract class AbstractTestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();
    }
}
