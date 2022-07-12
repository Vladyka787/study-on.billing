<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    private $em;
    private $replenishmentAmount;

    public function __construct(EntityManagerInterface $em, int $replenishmentAmount)
    {
        $this->em = $em;
        $this->replenishmentAmount = $replenishmentAmount;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function topUpYourAccount(User $user, float $deposit = null, bool $firstDeposit = false)
    {
        $em = $this->em;

        if ($firstDeposit) {
            $deposit = $this->replenishmentAmount;
        }

        if ($deposit === null) {
            return false;
        }

        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $newBalance = $user->getBalance() + $deposit;
            $user->setBalance($newBalance);

            $now = new \DateTime("now", new \DateTimeZone('UTC'));

            $transaction = new Transaction();

            $transaction->setClient($user);
            $transaction->setValue($deposit);
            $transaction->setDateAndTime($now);
            $transaction->setTypeOfTransaction('deposit');

            $em->persist($transaction);
            $em->persist($user);

            $em->flush();
            $em->getConnection()->commit();

            return true;
        } catch (Exception $e) {
            $em->getConnection()->rollBack();
            throw $e;
        }
    }

    public function paymentCourse(User $user, Course $course)
    {
        $em = $this->em;

        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            if ($user->getBalance() < $course->getPrice()) {
                return false;
            }

            $newBalance = $user->getBalance() - $course->getPrice();
            $user->setBalance($newBalance);

            $transaction = new Transaction();

            $now = new \DateTime("now", new \DateTimeZone('UTC'));
            $until = new \DateTime("now", new \DateTimeZone('UTC'));
            $until->modify('+7 day');

            $transaction->setClient($user);
            $transaction->setCourse($course);
            $transaction->setValue($course->getPrice());
            $transaction->setDateAndTime($now);
            if ($course->getType() === 'rent') {
                $transaction->setValidUntil($until);
            }
            $transaction->setTypeOfTransaction('payment');

            $em->persist($transaction);
            $em->persist($user);

            $em->flush();
            $em->getConnection()->commit();

            return true;
        } catch (Exception $e) {
            $em->getConnection()->rollBack();
            throw $e;
        }
    }
}