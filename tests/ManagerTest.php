<?php
namespace Ray\Di;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    protected $forge;

    protected $manager;

    protected function setUp()
    {
        parent::setUp();
        $this->config  = new Config;
        $this->forge   = new Forge($this->config);
        $this->manager = new Manager($this->forge);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testNewAndGetContainer()
    {
        $this->manager->params['Ray\Di\MockParentClass']['foo'] = 'dib';

        $mock = $this->manager->newContainer('mock');
        $mock->params['Ray\Di\MockParentClass']['foo'] = 'zim';

        // make sure two containers give different objects
        $a = $this->manager->newInstance('Ray\Di\MockParentClass');
        $b = $mock->newInstance('Ray\Di\MockParentClass');

        $this->assertNotSame($a->getFoo(), $b->getFoo());

        // can we get the container?
        $actual = $this->manager->getContainer('mock');
        $this->assertSame($mock, $actual);
    }

    /**
     * @expectedException Ray\Di\Exception\ContainerExists
     */
    public function testNewContainerExists()
    {
        $mock = $this->manager->newContainer('mock');
        $mock = $this->manager->newContainer('mock');
    }

    /**
     * @expectedException Ray\Di\Exception\ContainerNotFound
     */
    public function testGetContainerNotFound()
    {
        $mock = $this->manager->getContainer('mock');
    }

    public function testLock()
    {
        $mock = $this->manager->newContainer('mock');
        $this->manager->lock();
        $this->assertTrue($this->manager->isLocked());
        foreach ($this->manager->getContainers() as $name) {
            $container = $this->manager->getContainer($name);
            $this->assertTrue($container->isLocked());
        }
    }

    public function testCloneContainer()
    {
        $mock = $this->manager->newContainer('mock');
        $mock->set('parent', function() use ($mock) {
            return $mock->newInstance('Ray\Di\MockParentClass');
        });

        $clone = $this->manager->cloneContainer('mock');

        $this->assertNotSame($mock, $clone);
        $this->assertNotSame($mock->get('parent'), $clone->get('parent'));
    }

    public function testLazyCloneContainer()
    {
        $mock = $this->manager->newContainer('mock');
        $mock->set('parent', function() use ($mock) {
            return $mock->newInstance('Ray\Di\MockParentClass');
        });

        $lazy = $this->manager->lazyCloneContainer('mock');
        $this->assertInstanceOf('Ray\Di\Lazy', $lazy);
        $mock = $lazy();
        $this->assertInstanceOf('Ray\Di\Container', $mock);
    }

    public function testSubContainer()
    {
        $expect = $this->manager->subContainer('mock');
        $actual = $this->manager->subContainer('mock');
        $this->assertSame($expect, $actual);
    }
}