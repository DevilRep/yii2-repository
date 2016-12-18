<?php

namespace devilrep\repository\criteria;

use devilrep\repository\contracts\Criteria;
use yii\db\ActiveQuery as Query;
use yii\db\ActiveQueryInterface;
use devilrep\repository\contracts\Repository;

class Where implements Criteria
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var string|array
     */
    private $value;

    /**
     * @var string
     */
    private $operator;

    public function __construct(string $column, $value, string $operator = '=')
    {
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * @param Query|ActiveQueryInterface $query
     * @param Repository $repository
     * @return Query|ActiveQueryInterface
     */
    public function apply($query, Repository $repository)
    {
        if ($this->operator === '=') {
            return $query->andWhere([ $this->column => $this->value ]);
        }

        return $query->andWhere([
            $this->operator,
            $this->column,
            $this->value
        ]);
    }
}