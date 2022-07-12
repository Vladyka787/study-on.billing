<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function add(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getDataAllCourses(): array
    {
        $courses = $this->findAll();
        $result = [];

        foreach ($courses as $course) {
            $arr = [];
            $arr['code'] = $course->getCharacterCode();
            $arr['type'] = $course->getType();
            if ($course->getPrice() !== null) {
                $arr['price'] = $course->getPrice();
            }
            $result[] = $arr;
        }

        return $result;
    }

    public function getCourseDataByCharacterCode(string $code): array
    {
        $course = $this->findOneBy(['CharacterCode' => $code]);

        $result = [];
        if ($course !== null) {
            $result['code'] = $course->getCharacterCode();
            $result['type'] = $course->getType();
            if ($course->getPrice() !== null) {
                $result['price'] = $course->getPrice();
            }
        }

        return $result;
    }

//    /**
//     * @return Course[] Returns an array of Course objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Course
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
