<?php

namespace devilrep\repository\contracts;

use yii\db\ActiveRecordInterface as Model;
use yii\base\Model as DbModel;
use devilrep\repository\exceptions\ModelNotFound as ModelNotFoundException;
use devilrep\repository\exceptions\ScenarioNotFound as ScenarioNotFoundException;
use devilrep\repository\exceptions\ModelNotUpdated as ModelNotUpdatedException;

interface Repository
{
    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return array
     */
    public function all(array $columns = ['*']);

    /**
     * Retrieve one record from repository
     *
     * @param array $columns
     * @return Model|array
     * @throws ModelNotFoundException
     */
    public function first($columns = ['*']);

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int $page_number
     * @param int $page_size
     * @param array $columns
     * @return array
     */
    public function paginate(int $page_number, int $page_size = 10, array $columns = ['*']);

    /**
     * Find data by id
     *
     * @param int $id
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $columns = ['*']);

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return array
     * @throws ModelNotFoundException
     */
    public function findWhere(array $where, array $columns = ['*']);

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     * @param string $scenario
     * @return Model
     * @throws ScenarioNotFoundException
     * @throws ModelNotUpdatedException
     */
    public function create(array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT);

    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param int $id
     * @return Model
     * @throws ModelNotFoundException
     * @throws ScenarioNotFoundException
     * @throws ModelNotUpdatedException
     */
    public function update(int $id, array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT);

    /**
     * Update or Create an entity in repository
     *
     * @param int $id
     * @param array $attributes
     * @return Model
     * @throws ScenarioNotFoundException
     * @throws ModelNotUpdatedException
     */
    public function updateOrCreate(int $id, array $attributes, string $scenario = DbModel::SCENARIO_DEFAULT);

    /**
     * Delete a entity in repository by id
     *
     * @param int $id
     * @return Model
     * @throws ModelNotFoundException
     * @throws ModelNotUpdatedException
     */
    public function delete(int $id);

    /**
     * Order collection by a given column
     *
     * @param array $columns
     * @return $this
     */
    public function orderBy(array $columns);

    /**
     * Load relations
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations);
}
