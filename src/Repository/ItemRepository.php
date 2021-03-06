<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Source;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;

/**
 * ItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ItemRepository extends ServiceEntityRepository
{
    use PaginatorTrait;

    const EVENT_LIFETIME = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param int $results
     * @return Item[]
     */
    public function findLast($results): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->addSelect('s')
            ->orderBy('i.publishedAt', 'DESC')
            ->setMaxResults($results)
            ->getQuery()
            ->getResult();
    }

    public function findMainPaginated(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->addSelect('s')
            ->where('s.visibility = :visibility')
            ->andWhere('s.isEnabled = true')
            ->setParameter('visibility', Source::VISIBILITY_MAIN)
            ->orderBy('i.publishedAt', 'DESC')
            ->getQuery();

        return $this->paginate($query, $page, $limit);
    }

    public function findUserFeedPaginated(User $user, int $page, int $limit): Paginator
    {
        $currentDate = new \DateTime;

        $qb = $this->createQueryBuilder('i');
        $qb->addSelect('s')
            ->leftJoin('i.source', 's')
            ->leftJoin('s.subscriptions', 'sb')
            ->where('sb.user = :user')
            ->andWhere('s.isEnabled = true')
            ->andWhere('sb.expireAt IS NULL OR sb.expireAt > :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('user', $user)
            ->orderBy('i.publishedAt', 'DESC');
        $this->applyVisibilityCriteria($qb, $user);

        return $this->paginate($qb->getQuery(), $page, $limit);
    }

    public function findPaginatedByTagId(int $id, int $page, int $limit, ?User $user): Paginator
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->leftJoin('s.tags', 't')
            ->addSelect('s')
            ->where('t.id = :tag_id')
            ->andWhere('s.isEnabled = true')
            ->setParameter('tag_id', $id)
            ->orderBy('i.publishedAt', 'DESC');
        $this->applyVisibilityCriteria($qb, $user);

        return $this->paginate($qb->getQuery(), $page, $limit);
    }

    public function findEventsPaginated(int $page, int $limit, ?User $user): Paginator
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->leftJoin('s.tags', 't')
            ->addSelect('s')
            ->where('i.startDate IS NOT NULL')
            ->andWhere('i.startDate > :date')
            ->andWhere('s.isEnabled = true')
            ->setParameter('date', new \DateTime(sprintf('-%s days', self::EVENT_LIFETIME)))
            ->orderBy('i.startDate', 'ASC');
        $this->applyVisibilityCriteria($qb, $user);

        return $this->paginate($qb->getQuery(), $page, $limit);
    }

    public function getQueryBuilderBySourceId(int $id): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->addSelect('s')
            ->where('s.id = :source_id')
            ->setParameter('source_id', $id);
    }

    /**
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function getCountBySourceId(int $id): int
    {
        return $this->getQueryBuilderBySourceId($id)
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPaginatedBySourceId(int $id, int $page, int $limit): Paginator
    {
        $query = $this->getQueryBuilderBySourceId($id)
            ->orderBy('i.publishedAt', 'DESC')
            ->getQuery();

        return $this->paginate($query, $page, $limit);
    }

    public function findPaginatedByPhrase(string $phrase, int $page, int $limit, ?User $user): Paginator
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.source', 's')
            ->addSelect('s')
            ->orderBy('i.publishedAt', 'DESC');
        $this->applyVisibilityCriteria($qb, $user);

        foreach (explode(' ', $phrase) as $i => $word) {
            $qb->andWhere('i.title like :word_' . $i)
                ->setParameter('word_' . $i, '%' . $word . '%');
        }

        return $this->paginate($qb->getQuery(), $page, $limit);
    }

    public function deleteOld(): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.id')
            ->join('i.source', 's')
            ->where("i.publishedAt < DATE_SUB(CURRENT_DATE(), (s.itemsDaysToLive - 1), 'day')")
            ->getQuery()->getArrayResult();

        $ids = array_column($result, "id");

        return $this->createQueryBuilder('i')
            ->delete()
            ->where("i.id IN(:ids)")
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    public function deleteBySource(Source $source): int
    {
        return $this->createQueryBuilder('i')
            ->delete()
            ->where('i.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();
    }

    private function applyVisibilityCriteria(QueryBuilder $qb, ?User $user)
    {
        $exprForPrivate = null;
        $exprForProtected = null;
        $exprForPublic = $qb->expr()->in('s.visibility', ':public');

        if ($user) {
            $exprForPrivate = $qb->expr()->andX(
                $qb->expr()->eq('s.visibility', ':private'),
                $qb->expr()->eq('s.createdBy', ':user')
            );
            $exprForProtected = $qb->expr()->eq('s.visibility', ':protected');
            $qb->setParameter('user', $user)
                ->setParameter('private', Source::VISIBILITY_PRIVATE)
                ->setParameter('protected', Source::VISIBILITY_PROTECTED);
        }

        $qb->andWhere($qb->expr()->orX($exprForPublic, $exprForProtected, $exprForPrivate))
            ->setParameter('public', [
                Source::VISIBILITY_MAIN,
                Source::VISIBILITY_PUBLIC,
            ]);
    }
}
