<?php

namespace Orchestra\Html\TestCase;

use Orchestra\Html\Grid;
use Illuminate\Container\Container;

class GridTest extends \PHPUnit_Framework_TestCase
{
    public function testMetaData()
    {
        $app = new Container();
        $stub = new GridStub($app, []);

        $refl = new \ReflectionObject($stub);
        $meta = $refl->getProperty('meta');
        $meta->setAccessible(true);

        $this->assertEquals([], $meta->getValue($stub));

        $stub->set('foo.bar', 'foobar');
        $stub->set('foo.hello', 'world');

        $this->assertEquals(['foo' => ['bar' => 'foobar', 'hello' => 'world']], $meta->getValue($stub));
        $this->assertEquals('foobar', $stub->get('foo.bar'));
        $this->assertNull($stub->get('foobar'));

        $stub->forget('foo.bar');

        $this->assertEquals(['foo' => ['hello' => 'world']], $meta->getValue($stub));
        $this->assertNull($stub->get('foo.bar'));
    }
}

class GridStub extends Grid
{
    public function find($name)
    {
        //
    }
}
