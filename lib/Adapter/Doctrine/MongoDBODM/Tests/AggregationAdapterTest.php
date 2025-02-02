<?php declare(strict_types=1);

namespace Pagerfanta\Doctrine\MongoDBODM\Tests;

use Doctrine\ODM\MongoDB\Aggregation\Aggregation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Count;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Skip;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Pagerfanta\Doctrine\MongoDBODM\AggregationAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AggregationAdapterTest extends TestCase
{
    /**
     * @var MockObject&Builder
     */
    private $aggregationBuilder;

    /**
     * @var AggregationAdapter<mixed>
     */
    private $adapter;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(DocumentManager::class)) {
            self::markTestSkipped('doctrine/mongodb-odm is not installed');
        }
    }

    protected function setUp(): void
    {
        $this->aggregationBuilder = $this->createMock(Builder::class);

        $this->adapter = new AggregationAdapter($this->aggregationBuilder);
    }

    public function testGetNbResultsShouldResetHydrationAndAddCountStage(): void
    {
        $aggregation = $this->createMock(Aggregation::class);
        $resultIterator = $this->createMock(Iterator::class);
        $countStage = $this->createMock(Count::class);

        $countStage->expects(self::once())
            ->method('getAggregation')
            ->willReturn($aggregation);

        $resultIterator->expects(self::once())
            ->method('toArray')
            ->willReturn([['numResults' => 110]]);

        $aggregation->expects(self::once())
            ->method('getIterator')
            ->willReturn($resultIterator);

        $this->aggregationBuilder->expects(self::once())
            ->method('hydrate')
            ->with(null)
            ->willReturnSelf();

        $this->aggregationBuilder->expects(self::once())
            ->method('count')
            ->with('numResults')
            ->willReturn($countStage);

        $this->assertSame(110, $this->adapter->getNbResults());
    }

    public function testGetSlice(): void
    {
        $offset = 10;
        $length = 15;
        $slice = $this->createMock(Iterator::class);

        $aggregation = $this->createMock(Aggregation::class);
        $skipStage = $this->createMock(Skip::class);

        $skipStage->expects(self::once())
            ->method('limit')
            ->with($length)
            ->willReturn($this->aggregationBuilder);

        $this->aggregationBuilder->expects(self::once())
            ->method('getAggregation')
            ->willReturn($aggregation);

        $aggregation->expects(self::once())
            ->method('getIterator')
            ->willReturn($slice);

        $this->aggregationBuilder->expects(self::once())
            ->method('skip')
            ->with($offset)
            ->willReturn($skipStage);

        $this->assertSame($slice, $this->adapter->getSlice($offset, $length));
    }
}
