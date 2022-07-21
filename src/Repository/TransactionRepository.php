<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getExpiresDateByUserAndCharacterCode(User $client, Course $course)
    {
        if ($course->getType() === 'rent') {
            $transaction = $this->findOneBy(['Course' => $course, 'Client' => $client]);
            $date = $transaction->getValidUntil();
            $result = $date->format('Y-m-d\TH:i:sP');
        } else {
            $result = null;
        }
        return $result;
    }

    public function getAllTransactionByUser(User $client, array $filter = null)
    {
        $transactions = $this->findBy(['Client' => $client]);

        $result = [];
        foreach ($transactions as $transaction) {
            $arr = [];
            if ($this->workFilter($filter, $transaction)) {
                $arr['id'] = $transaction->getId();
                $dataCreate = $transaction->getDateAndTime();
                $arr['created_at'] = $dataCreate->format('Y-m-d\TH:i:sP');
                $arr['type'] = $transaction->getTypeOfTransaction();
                if ($arr['type'] === 'payment') {
                    $arr['course_code'] = $transaction->getCourse()->getCharacterCode();
                }
                $arr['amount'] = $transaction->getValue();
                $result[] = $arr;
            }
        }

        return $result;
    }

    private function workFilter($filter = null, Transaction $transaction)
    {
        if ($filter !== null) {
            if (array_key_exists('type', $filter)) {
                if ($filter['type'] === $transaction->getTypeOfTransaction()) {
                } else {
                    return false;
                }
            }
            if (array_key_exists('course_code', $filter)) {
                if ($transaction->getCourse() !== null) {
                    if ($filter['course_code'] === $transaction->getCourse()->getCharacterCode()) {
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            if (array_key_exists('skip_expired', $filter)) {
                if ($filter['skip_expired'] == "true") {
                    if ($transaction->getValidUntil() !== null) {
                        $now = strtotime('now');
                        $then = strtotime($transaction->getValidUntil()->format('Y-m-d H:i:sP'));
                        if ($now < $then) {

                        } else {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
