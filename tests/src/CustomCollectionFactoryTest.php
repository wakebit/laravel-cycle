<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests;

use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Cycle\ORM\FactoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class CustomCollectionFactoryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        /** @var ConfigRepository */
        $config = $app->get(ConfigRepository::class);
        $config->set('cycle.orm.default_collection_factory_class', IlluminateCollectionFactory::class);
    }

    public function testLaravelCollection(): void
    {
        /** @var FactoryInterface $factory */
        $factory = $this->app->get(FactoryInterface::class);
        $collection = $factory->collection()->collect([1, 2, 3]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $collection);
    }
}
