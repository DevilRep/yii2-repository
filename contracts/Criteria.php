<?php

namespace devilrep\repository\contracts;

use yii\db\ActiveQuery as Query;
use yii\db\ActiveQueryInterface;

interface Criteria
{
    /**
     * Apply criteria in query repository
     *
     * @param Query|ActiveQueryInterface $query
     * @param Repository $repository
     * @return Query|ActiveQueryInterface
     */
    public function apply($query, Repository $repository);
}
