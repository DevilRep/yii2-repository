<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;
use yii\di\Container;
use yii\db\ActiveQueryInterface as Query;
use yii\base\Model as DbModel;
use yii\data\Pagination;
use devilrep\repository\contracts\Criteria;
use devilrep\repository\exceptions\Repository as RepositoryException;
use devilrep\repository\exceptions\CriteriaNotFound as CriteriaNotFoundException;
use devilrep\repository\exceptions\ModelNotFound as ModelNotFoundException;
use devilrep\repository\exceptions\ModelNotUpdated as ModelNotUpdatedException;
use devilrep\repository\exceptions\ScenarioNotFound as ScenarioNotFoundException;
use yiiunit\extensions\repository\fixtures\TestModel;
use yiiunit\extensions\repository\fixtures\TestRepository;

class RepositoryTest extends TestCase
{
    /**
     * @var TestRepository
     */
    protected $repository;

    /**
     * @var MockInterface
     */
    protected $container;

    /**
     * @var MockInterface
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();
        $this->model = Mockery::mock(TestModel::class);
        $this->container = Mockery::mock(Container::class);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->repository = new TestRepository($this->container);
    }

    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function prepareModelAndContainer()
    {
        $query = Mockery::mock(Query::class);
        $this->model
            ->shouldReceive('find')
            ->andReturn($query);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        return $query;
    }

    public function testModelMakeThrowModelNotUpdatedException()
    {
        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn(new RepositoryTest());

        $this->expectException(RepositoryException::class);
        $this->repository->modelMake();
    }

    public function testModelMakeSuccess()
    {
        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->assertEquals($this->repository->modelMake(), $this->model);
    }

    public function testModelReset()
    {
        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->assertEquals($this->repository->modelReset(), $this->repository);
    }

    public function testCriteriaGet()
    {
        $criteria = Mockery::mock(Criteria::class);
        $this->repository->criteriaInit([$criteria]);
        $this->assertEquals($this->repository->criteriaGet(), [$criteria]);
    }

    public function testCriteriaPush()
    {
        $criteria = Mockery::mock(Criteria::class);
        $this->assertEquals($this->repository->criteriaPush($criteria)->criteriaGet(), [$criteria]);
    }

    public function testCriteriaPopThrowCriteriaNotFoundException()
    {
        $this->expectException(CriteriaNotFoundException::class);
        $this->repository->criteriaPop();
    }

    public function testCriteriaPopSuccess()
    {
        $criteria1 = Mockery::mock(Criteria::class);
        $criteria2 = Mockery::mock(Criteria::class);
        $this->repository->criteriaInit([$criteria1, $criteria2]);
        $this->assertEquals($this->repository->criteriaPop(), $criteria2);
    }

    public function testCriteriaUse()
    {
        $data = ['test' => 1];

        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('all')
            ->andReturn($data);

        $criteria = Mockery::mock(Criteria::class);
        $criteria
            ->shouldReceive('apply')
            ->andReturn($query);

        $this->assertEquals($this->repository->criteriaUse($criteria), $data);
    }

    public function testCriteriaApply()
    {
        $query = Mockery::mock(Query::class);

        $criteria = Mockery::mock(Criteria::class);
        $criteria
            ->shouldReceive('apply')
            ->andReturn($query);

        $this->model
            ->shouldReceive('find')
            ->andReturn($query);

        $this->repository->criteriaInit([$criteria]);

        $this->assertEquals($this->repository->criteriaApply()->getQuery(), $query);
    }

    public function testCriteriaSkip()
    {
        $criteria = Mockery::mock(Criteria::class);
        $this->repository->criteriaInit([$criteria]);

        $this->assertEquals($this->repository->criteriaSkip()->criteriaApply()->getQuery(), null);
    }

    public function testCriteriaSkipOnlyOnce()
    {
        $query = Mockery::mock(Query::class);

        $criteria = Mockery::mock(Criteria::class);
        $criteria
            ->shouldReceive('apply')
            ->andReturn($query);

        $this->model
            ->shouldReceive('find')
            ->andReturn($query);

        $this->repository->criteriaInit([$criteria]);

        $this->assertEquals($this->repository->criteriaSkip()->criteriaApply()->criteriaApply()->getQuery(), $query);
    }

    public function testCriteriaReset()
    {
        $criteria = Mockery::mock(Criteria::class);
        $this->repository->criteriaInit([$criteria]);

        $this->assertEquals($this->repository->criteriaReset()->criteriaGet(), []);
    }

    public function testAll()
    {
        $data = ['test' => 2];

        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('all')
            ->andReturn($data)
            ->shouldReceive('select')
            ->andReturnSelf();

        $this->assertEquals($this->repository->all(), $data);
    }

    public function testFirstThrowModelNotFoundException()
    {
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturnNull()
            ->shouldReceive('select')
            ->andReturnSelf();

        $this->expectException(ModelNotFoundException::class);
        $this->repository->first();
    }

    public function testFirstSuccess()
    {
        $data = ['test' => 3];

        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturn($data)
            ->shouldReceive('select')
            ->andReturnSelf();

        $this->assertEquals($this->repository->first(), $data);
    }

    public function testPaginate()
    {
        $pagination = new Pagination([
            'totalCount' => 9,
            'pageSize' => 2,
            'page' => 3
        ]);
        $data = [
            'data' => ['test1' => 4, 'test2' => 5],
            'pagination' => $pagination
        ];

        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('all')
            ->andReturn(['test1' => 4, 'test2' => 5])
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('limit')
            ->andReturnSelf()
            ->shouldReceive('offset')
            ->andReturnSelf()
            ->shouldReceive('count')
            ->andReturn(9);

        $this->container
            ->shouldReceive('get')
            ->withArgs([
                Pagination::class,
                [
                    'totalCount' => 9,
                    'pageSize' => 2,
                    'page' => 3
                ]
            ])
            ->once()
            ->andReturn($pagination);

        $this->assertEquals($this->repository->paginate(3, 2), $data);
    }

    public function testFindThrowModelNotFoundException()
    {
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturnNull()
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('primaryKey')
            ->andReturn(['test']);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->find(0);
    }

    public function testFind()
    {
        $data = ['test' => 6];
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturn($data)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('primaryKey')
            ->andReturn(['test']);

        $this->assertEquals($this->repository->find(6), $data);
    }

    public function testFindWhere()
    {
        $data = ['test' => 7];

        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturn($data)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->assertEquals($this->repository->findWhere([
            'test' => 7
        ]), $data);
    }

    public function testCreateThrowScenarioNotFoundException()
    {
        $this->model
            ->shouldReceive('scenarios')
            ->andReturn(['test' => []]);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->expectException(ScenarioNotFoundException::class);
        $this->repository->create(['test' => 8], DbModel::SCENARIO_DEFAULT);
    }

    public function testCreateThrowNotProcessedException()
    {
        $this->model
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test']])
            ->shouldReceive('save')
            ->andReturn(false)
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->expectException(ModelNotUpdatedException::class);
        $this->repository->create(['test' => 9]);
    }

    public function testCreateSuccess()
    {
        $this->model
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test']])
            ->shouldReceive('save')
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->assertEquals($this->repository->create(['test' => 10]), $this->model);
    }

    public function testUpdateThrowScenarioNotFoundException()
    {
        $this->model
            ->shouldReceive('scenarios')
            ->andReturn(['test' => ['test']]);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->expectException(ScenarioNotFoundException::class);
        $this->repository->update(0, ['test' => 11], DbModel::SCENARIO_DEFAULT);
    }

    public function testUpdateThrowModelNotFoundException()
    {
        $query = Mockery::mock(Query::class);
        $query
            ->shouldReceive('one')
            ->andReturnNull()
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('find')
            ->andReturn($query)
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test']])
            ->shouldReceive('primaryKey')
            ->andReturn(['test']);

        $this->container
            ->shouldReceive('get')
            ->twice()
            ->andReturn($this->model);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->update(0, ['test' => 11]);
    }

    public function testUpdateThrowNotProcessedException()
    {
        $query = Mockery::mock(Query::class);
        $query
            ->shouldReceive('one')
            ->andReturn($this->model)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('find')
            ->andReturn($query)
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test2']])
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true)
            ->shouldReceive('save')
            ->andReturn(false);

        $this->container
            ->shouldReceive('get')
            ->twice()
            ->andReturn($this->model);

        $this->expectException(ModelNotUpdatedException::class);
        $this->repository->update(10, ['test2' => 2]);
    }

    public function testUpdateSuccess()
    {
        $query = Mockery::mock(Query::class);
        $query
            ->shouldReceive('one')
            ->andReturn($this->model)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('find')
            ->andReturn($query)
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test2']])
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true)
            ->shouldReceive('save')
            ->andReturn(true);

        $this->container
            ->shouldReceive('get')
            ->twice()
            ->andReturn($this->model);

        $this->assertEquals($this->repository->update(10, ['test2' => 2]), $this->model);
    }

    public function testUpdateOrCreateThrowScenarioNotFoundException()
    {
        $this->model
            ->shouldReceive('scenarios')
            ->andReturn(['test' => ['test2']]);

        $this->container
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->model);

        $this->expectException(ScenarioNotFoundException::class);
        $this->repository->updateOrCreate(0, ['test2' => 3], DbModel::SCENARIO_DEFAULT);
    }

    public function testUpdateOrCreateCreate()
    {
        $query = Mockery::mock(Query::class);
        $query
            ->shouldReceive('one')
            ->andReturnNull()
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test2']])
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('find')
            ->andReturn($query)
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true)
            ->shouldReceive('save')
            ->andReturn(true);

        $this->container
            ->shouldReceive('get')
            ->times(3) // first when while checking scenario, second - searching, third - while creating
            ->andReturn($this->model);

        $this->assertEquals($this->repository->updateOrCreate(0, ['test2' => 3]), $this->model);
    }

    public function testUpdateOrCreateUpdate()
    {
        $query = Mockery::mock(Query::class);
        $query
            ->shouldReceive('one')
            ->andReturn($this->model)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('find')
            ->andReturn($query)
            ->shouldReceive('scenarios')
            ->andReturn([DbModel::SCENARIO_DEFAULT => ['test2']])
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('hasAttribute')
            ->withArgs(['attributes'])
            ->andReturn(true)
            ->shouldReceive('hasAttribute')
            ->withArgs(['scenario'])
            ->andReturn(true)
            ->shouldReceive('save')
            ->andReturn(true);

        $this->container
            ->shouldReceive('get')
            ->twice()
            ->andReturn($this->model);

        $this->assertEquals($this->repository->updateOrCreate(1, ['test2' => 4]), $this->model);
    }

    public function testDeleteThrowModelNotFoundException()
    {
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturnNull()
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('primaryKey')
            ->andReturn(['test']);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->delete(0);
    }

    public function testDeleteThrowModelNotUpdatedException()
    {
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturn($this->model)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('delete')
            ->andReturn(false);

        $this->expectException(ModelNotUpdatedException::class);
        $this->repository->delete(1);
    }

    public function testDeleteSuccess()
    {
        $query = $this->prepareModelAndContainer();
        $query
            ->shouldReceive('one')
            ->andReturn($this->model)
            ->shouldReceive('select')
            ->andReturnSelf()
            ->shouldReceive('andWhere')
            ->andReturnSelf();

        $this->model
            ->shouldReceive('primaryKey')
            ->andReturn(['test'])
            ->shouldReceive('delete')
            ->andReturn(0);

        $this->assertEquals($this->repository->delete(1), $this->model);
    }

    public function testOrderByThrowRepositoryException()
    {
        $this->expectException(RepositoryException::class);
        $this->repository->orderBy(['test' => 'test2']);
    }

    public function testOrderBySuccess()
    {
        $query = Mockery::mock(Query::class);
        $this->model
            ->shouldReceive('find')
            ->andReturn($query);

        $query
            ->shouldReceive('orderBy')
            ->withArgs([['test' => 'desc']])
            ->andReturnSelf();

        $this->assertEquals($this->repository->orderBy(['test' => 'desc']), $this->repository);
    }

    public function testWith()
    {
        $query = Mockery::mock(Query::class);
        $this->model
            ->shouldReceive('find')
            ->andReturn($query);

        $query
            ->shouldReceive('with')
            ->andReturnSelf();

        $this->assertEquals($this->repository->with(['test1', 'test2']), $this->repository);
    }
}
