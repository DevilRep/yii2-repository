<?php

namespace yiiunit\extensions\repository\fixtures;

use yiiunit\extensions\repository\AbstractRepository;

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

    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    public function setFieldsSearchable($fields)
    {
        $this->fieldSearchable = $fields;
    }

    public function criteriaInit(array $criteria)
    {
        $this->criteria = $criteria;
    }
}
