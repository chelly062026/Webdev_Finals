<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Return associative array of status => count
     *
     * @return array<string,int>
     */
    public function getStatusCounts(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT status, COUNT(*) as cnt FROM `order` GROUP BY status';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        $counts = [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($result as $row) {
            $s = $row['status'] ?? '';
            $counts[$s] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Get recent orders ordered by createdAt desc
     *
     * @return Order[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    /**
     * Sum of total_amount (as float)
     */
    public function getTotalRevenue(): float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT SUM(total_amount) as total FROM `order`';
        $stmt = $conn->prepare($sql);
        $row = $stmt->executeQuery()->fetchAssociative();
        return isset($row['total']) ? (float) $row['total'] : 0.0;
    }

    /**
     * @return Order[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.Customer', 'c')
            ->where('o.createdBy = :user')
            ->orWhere('c.email = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestForUser(User $user): ?Order
    {
        return $this->findForUser($user)[0] ?? null;
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
