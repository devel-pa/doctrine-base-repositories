<?php

/*
 * doctrine-repositories (https://github.com/juliangut/doctrine-repositories).
 * Doctrine2 utility repositories.
 *
 * @license MIT
 * @link https://github.com/juliangut/doctrine-repositories
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

namespace Jgut\Doctrine\Repository\Tests;

use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Jgut\Doctrine\Repository\MongoDBRepository;
use Jgut\Doctrine\Repository\Tests\Stubs\EntityDocumentStub;
use Zend\Paginator\Paginator;

/**
 * MongoDB repository tests.
 *
 * @group mongodb
 */
class MongoDBRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDocumentName()
    {
        $manager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var DocumentManager $manager */

        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var UnitOfWork $uow */

        $repository = new MongoDBRepository($manager, $uow, new ClassMetadata(EntityDocumentStub::class));

        static::assertEquals(EntityDocumentStub::class, $repository->getClassName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Criteria must be an array of query fields or a Doctrine\ODM\MongoDB\Query\Builder
     */
    public function testInvalidCriteria()
    {
        $manager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var DocumentManager $manager */

        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var UnitOfWork $uow */

        $repository = new MongoDBRepository($manager, $uow, new ClassMetadata(EntityDocumentStub::class));

        $repository->findPaginatedBy('');
    }

    public function testFindPaginated()
    {
        $cursor = $this->getMockBuilder(Cursor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects(static::at(0))
            ->method('execute')
            ->will(static::returnValue($cursor));

        $manager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var DocumentManager $manager */

        $queryBuilder = $this->getMockBuilder(Builder::class)
            ->setConstructorArgs([$manager])
            ->setMethodsExcept(['addAnd', 'refresh', 'sort', 'skip', 'limit'])
            ->getMock();
        $queryBuilder->expects(static::once())
            ->method('getQuery')
            ->will(static::returnValue($query));
        /* @var Builder $queryBuilder */

        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var UnitOfWork $uow */

        $repository = new MongoDBRepository($manager, $uow, new ClassMetadata(EntityDocumentStub::class));

        static::assertInstanceOf(Paginator::class, $repository->findPaginatedBy($queryBuilder, ['fakeField' => 'ASC']));
    }

    public function testCount()
    {
        $expr = $this->getMockBuilder(Expr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expr->expects(static::any())
            ->method('field')
            ->will(static::returnSelf());
        $expr->expects(static::any())
            ->method('equals')
            ->will(static::returnSelf());
        $expr->expects(static::any())
            ->method('in')
            ->will(static::returnSelf());

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects(static::exactly(2))
            ->method('execute')
            ->will(static::returnSelf());
        $query->expects(static::exactly(2))
            ->method('count')
            ->will(static::returnValue(10));

        $manager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder = $this->getMockBuilder(Builder::class)
            ->setConstructorArgs([$manager])
            ->setMethodsExcept(['addAnd', 'refresh'])
            ->getMock();
        $queryBuilder->expects(static::exactly(2))
            ->method('getQuery')
            ->will(static::returnValue($query));
        $queryBuilder->expects(static::any())
            ->method('expr')
            ->will(static::returnValue($expr));
        /* @var Builder $queryBuilder */

        $manager->expects(static::once())
            ->method('createQueryBuilder')
            ->will(static::returnValue($queryBuilder));
        /* @var DocumentManager $manager */

        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        /* @var UnitOfWork $uow */

        $repository = new MongoDBRepository($manager, $uow, new ClassMetadata(EntityDocumentStub::class));

        static::assertEquals(10, $repository->countBy($queryBuilder));
        static::assertEquals(10, $repository->countBy(['fakeField' => 'fakeValue', 'arrayFakeField' => []]));
    }
}
