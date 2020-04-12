<?php

namespace App\Repository;

/**
 * SourceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SourceRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @var int|null $results
     * @return array
     * @throws \Exception
     */
    public function findForUpdate($results = null)
    {
        return $this->createQueryBuilder('s')
            ->where('s.scheduleAt <= :now')
            ->setParameter('now', new \DateTime())
            ->orWhere('s.scheduleAt is null')
            ->orderBy('s.scheduleAt')
            ->setMaxResults($results)
            ->getQuery()
            ->getResult();
    }
}
