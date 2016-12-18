<?php

namespace devilrep\repository;

use yii\di\Container;
use yii\db\ActiveQueryInterface;
use yii\db\BaseActiveRecord as Model;
use yii\db\ActiveQuery as Query;
use yii\base\Model as DbModel;
use yii\data\Pagination;
use devilrep\repository\contracts\RepositoryCriteria as RepositoryCriteriaInterface;
use devilrep\repository\contracts\Repository as RepositoryInterface;
use devilrep\repository\contracts\Criteria as CriteriaInterface;
use devilrep\repository\criteria\Where as WhereCriteria;
use devilrep\repository\exceptions\Repository as RepositoryException;
use devilrep\repository\exceptions\CriteriaNotFound as CriteriaNotFoundException;
use devilrep\repository\exceptions\ModelNotFound as ModelNotFoundException;
use devilrep\repository\exceptions\ModelNotUpdated as ModelNotUpdatedException;
use devilrep\repository\exceptions\ScenarioNotFound as ScenarioNotFoundException;

abstract class AbstractRepository implements RepositoryInterface, RepositoryCriteriaInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Array of criteria
     *
     * @var array
     */
    protected $criteria = [];

    /**
     * @var bool
     */
    protected $criteria_skip = false;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Query
     */
    protected $query;

    /**
     * AbstractRepository constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->criteria = [];
        $this->model = $this->modelMake();
        $this->boot();
    }

    public function boot()
    {
    }

    abstract protected function model();

    /**
     * @return Query|ActiveQueryInterface
     */
    protected function query()
    {
        if ($this->query) {
            return $this->query;
        }

        $this->query = $this->model->find();

        return $this->query;
    }

    /**
     * @return Query|ActiveQueryInterface
     */
    protected function queryMake()
    {
        return $this->modelMake()->find();
    }

    protected function queryReset()
    {
        $this->query = null;

        return $this;
    }

    protected function queryProcess(array $columns = ['*'])
    {
        $result = $this->query()->select($columns)->all();
        $this->modelReset()->queryReset();

        return $result;
    }

    /**
     * Make model
     *
     * @return Model
     * @throws RepositoryException
     */
    public function modelMake()
    {
        $model = $this->container->get($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException('Class ' . get_class($model) . ' must be an instance of ' . Model::class);
        }

        return $model;
    }

    /**
     * Reset model
     *
     * @return $this
     */
    public function modelReset()
    {
        $this->model = $this->modelMake();

        return $this;
    }

    /**
     * Get array of Criteria
     *
     * @return array
     */
    public function criteriaGet()
    {
        return $this->criteria;
    }

    /**
     * Push Criteria for filter the query
     *
     * @param CriteriaInterface $criteria
     * @return $this
     */
    public function criteriaPush(CriteriaInterface $criteria)
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    /**
     * Pop Criteria
     *
     * @return CriteriaInterface
     * @throws CriteriaNotFoundException
     */
    public function criteriaPop()
    {
        if (!count($this->criteria)) {
            throw new CriteriaNotFoundException();
        }

        return array_pop($this->criteria);
    }

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     * @return array
     */
    public function criteriaUse(CriteriaInterface $criteria)
    {
        return $criteria->apply($this->queryMake(), $this)->all();
    }

    /**
     * Apply all Criteria
     *
     * @return $this
     */
    public function criteriaApply()
    {
        if ($this->criteria_skip) {
            $this->criteria_skip = false;
            return $this;
        }

        foreach ($this->criteriaGet() as $criteria) {
            /**
             * @var CriteriaInterface $criteria
             */
            $this->query = $criteria->apply($this->query(), $this);
        }

        return $this;
    }

    /**
     * Skip Criteria: only once
     *
     * @return $this
     */
    public function criteriaSkip()
    {
        $this->criteria_skip = true;

        return $this;
    }

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function criteriaReset()
    {
        $this->criteria = [];

        return $this;
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return array
     */
    public function all(array $columns = ['*'])
    {
        return $this->criteriaApply()->queryProcess($columns);
    }

    /**
     * Retrieve one record from repository
     *
     * @param array $columns
     * @return Model|array
     * @throws ModelNotFoundException
     */
    public function first($columns = ['*'])
    {
        $result = $this->criteriaApply()->query()->select($columns)->one();
        $this->modelReset()->queryReset();

        if (!$result) {
            throw new ModelNotFoundException();
        }

        return $result;
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int $page_number
     * @param int $page_size
     * @param array $columns
     * @return array
     */
    public function paginate(int $page_number, int $page_size = 10, array $columns = ['*'])
    {
        $this->criteriaApply();
        $count = $this->query()->count();
        $data = $this->query()->select($columns)->limit($page_size)->offset($page_number * ($page_size - 1))->all();
        $this->modelReset()->queryReset();

        return [
            'data' => $data,
            'pagination' => $this->container->get(Pagination::class, [
                'totalCount' => $count,
                'pageSize' => $page_size,
                'page' => $page_number
            ])
        ];
    }

    /**
     * Find data by id
     *
     * @param int $id
     * @param array $columns
     * @return Model|array
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $columns = ['*'])
    {
        $criteria = new WhereCriteria(current($this->model->primaryKey()), $id);
        $result = $criteria->apply($this->queryMake(), $this)->select($columns)->one();

        if (!$result) {
            throw new ModelNotFoundException();
        }

        return $result;
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return array
     * @throws ModelNotFoundException
     */
    public function findWhere(array $where, array $columns = ['*'])
    {
        $query = $this->queryMake();
        foreach ($where as $column => $value) {
            $query = (new WhereCriteria($column, $value))->apply($query, $this);
        }

        return $query->select($columns)->one();
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     * @param string $scenario
     * @return Model
     * @throws ModelNotUpdatedException
     * @throws ScenarioNotFoundException
     */
    public function create(array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT)
    {
        $model = $this->modelMake();
        if (!in_array($scenario, array_keys($model->scenarios()))) {
            throw new ScenarioNotFoundException();
        }
        $model->scenario = $scenario;
        $model->attributes = $attributes;
        if (!$model->save()) {
            throw new ModelNotUpdatedException();
        }

        return $model;
    }

    /**
     * Update a entity in repository by id
     *
     * @param int $id
     * @param array $attributes
     * @param string $scenario
     * @return array|Model
     * @throws ModelNotUpdatedException
     * @throws ScenarioNotFoundException
     */
    public function update(int $id, array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT)
    {
        if (!in_array($scenario, array_keys($this->modelMake()->scenarios()))) {
            throw new ScenarioNotFoundException();
        }
        $model = $this->find($id);
        $model->scenario = $scenario;
        $model->attributes = $attributes;
        if (!$model->save()) {
            throw new ModelNotUpdatedException();
        }

        return $model;
    }

    /**
     * Update or Create an entity in repository
     *
     * @param int $id
     * @param array $attributes
     * @return Model
     */
    public function updateOrCreate(int $id, array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT)
    {
        try {
            return $this->update($id, $attributes);
        } catch (ModelNotFoundException $e) {
            return $this->create($attributes);
        }
    }

    /**
     * Delete a entity in repository by id
     *
     * @param int $id
     * @return Model
     * @throws ModelNotUpdatedException
     */
    public function delete(int $id)
    {
        $model = $this->find($id);

        // delete returns number of processed rows, but it can be 0 even if everything is ok
        if ($model->delete() === false) {
            throw new ModelNotUpdatedException();
        }
        return $model;
    }

    /**
     * Order collection by a given column
     *
     * @param array $columns
     * @return $this
     * @throws RepositoryException
     */
    public function orderBy(array $columns)
    {
        foreach ($columns as $direction) {
            if (in_array($direction, ['asc', 'desc'])) {
                continue;
            }
            throw new RepositoryException();
        }

        $this->query()->orderBy($columns);

        return $this;
    }

    /**
     * Load relations
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        foreach ($relations as $relation) {
            $this->query = $this->query()->with($relation);
        }

        return $this;
    }
}