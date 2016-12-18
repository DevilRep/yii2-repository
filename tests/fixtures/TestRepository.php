<?php

namespace yiiunit\extensions\repository\fixtures;

use devilrep\repository\AbstractRepository;

class TestRepository extends AbstractRepository
{
    public function model()
    {
        return TestModel::class;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function criteriaInit(array $criteria)
    {
        $this->criteria = $criteria;
    }
}
