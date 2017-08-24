<?php

namespace ParserBundle\Repository;

use ParserBundle\Entity\Source;

/**
 * SourceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SourceRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @return Source
     */
    public function findNextSource()
    {
        return $this->createQueryBuilder('sourse')
            ->orderBy('sourse.updatedAt')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
