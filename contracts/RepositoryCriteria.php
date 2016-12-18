<?php

namespace devilrep\repository\contracts;

use devilrep\repository\contracts\Criteria as CriteriaInterface;

interface RepositoryCriteria
{
    /**
     * Push Criteria for filter the query
     *
     * @param CriteriaInterface $criteria
     * @return $this
     */
    public function criteriaPush(CriteriaInterface $criteria);

    /**
     * Pop Criteria
     *
     * @return CriteriaInterface
     */
    public function criteriaPop();

    /**
     * Get array of Criteria
     *
     * @return array
     */
    public function criteriaGet();

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     * @return array
     */
    public function criteriaUse(CriteriaInterface $criteria);

    /**
     * Skip Criteria: only once
     *
     * @return $this
     */
    public function criteriaSkip();

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function criteriaReset();

    /**
     * Apply all Criteria
     *
     * @return $this
     */
    public function criteriaApply();
}
