<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Service\PaymentService;

class AppFixtures extends Fixture
{
    private $paymentService;
    private $doctrine;

    public function __construct(PaymentService $paymentService, ManagerRegistry $doctrine)
    {
        $this->paymentService = $paymentService;
        $this->doctrine = $doctrine;
    }

    public function load(ObjectManager $manager): void
    {
        $passwordHasherFactory = new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto'],
        ]);
        $hashPassword = new UserPasswordHasher($passwordHasherFactory);

        $userOne = new User();
        $userOne->setEmail('userOne@mail.ru');
        $hash = $hashPassword->hashPassword($userOne, 'Password');
        $userOne->setPassword($hash);

        $userTwo = new User();
        $userTwo->setEmail('userTwo@mail.ru');
        $hash = $hashPassword->hashPassword($userTwo, 'SuperPassword');
        $userTwo->setPassword($hash);
        $userTwo->setRoles(['ROLE_SUPER_ADMIN']);

        $userTest = new User();
        $userTest->setEmail('notWorkUser@mail.ru');
        $hash = $hashPassword->hashPassword($userTest, 'P');
        $userTest->setPassword($hash);

        $user1 = new User();
        $user1->setEmail('user1@mail.ru');
        $hash = $hashPassword->hashPassword($user1, 'password');
        $user1->setPassword($hash);

        $user2 = new User();
        $user2->setEmail('user2@mail.ru');
        $hash = $hashPassword->hashPassword($user2, 'password');
        $user2->setPassword($hash);

        $manager->persist($userOne);
        $manager->persist($userTwo);
        $manager->persist($userTest);
        $manager->persist($user1);
        $manager->persist($user2);

        $manager->flush();

        $this->paymentService->topUpYourAccount($userOne, null, true);
        $this->paymentService->topUpYourAccount($userTwo, null, true);
        $this->paymentService->topUpYourAccount($userTest, 10000000000000);
        $this->paymentService->topUpYourAccount($user1, null, true);
        $this->paymentService->topUpYourAccount($user2, null, true);


        $courseDataTitle = ["Курсы по стрижке",
            "Курсы по бегу",
            "Курсы по плаванью",
            "Курсы по прыжкам",
            "Курс для тестов"];

        $courseDataCharacterCode = ["kursy_po_strizhke",
            "kursy_po_begu",
            "kursy_po_plavaniyu",
            "kursy_po_pryzhkam",
            "infinity_money"];

        $courseDataType = ["rent",
            "rent",
            "buy",
            "free",
            "rent"];

        $courseDataPrice = [499.99,
            199.50,
            750.66,
            0,
            99999999];

        for ($i = 0; $i <= 4; $i++) {
            $course = new Course();
            $course->setTitle($courseDataTitle[$i]);
            $course->setCharacterCode($courseDataCharacterCode[$i]);
            $course->setType($courseDataType[$i]);
            if ($courseDataType[$i] != "free") {
                if (array_key_exists($i, $courseDataPrice)) {
                    $course->setPrice($courseDataPrice[$i]);
                }
            }
            $manager->persist($course);
        }

        $manager->flush();

        $courses = $this->doctrine->getRepository(Course::class)->findAll();

        foreach ($courses as $course) {
            if ($course->getType() != 'free') {
                $this->paymentService->paymentCourse($userTest, $course);
            }
        }

        foreach ($courses as $course) {
            if (($course->getType() != 'free') && $course->getPrice() < 10000) {
                $this->paymentService->paymentCourse($user1, $course);
                $this->paymentService->paymentCourse($user2, $course);
            }
        }

        $transactions = $manager->getRepository(Transaction::class)->findBy(['Client' => $user1]);

        foreach ($transactions as $transaction) {
            if ($transaction->getValidUntil() != null) {
                $transaction->setValidUntil(new \DateTime("-10 day", new \DateTimeZone('UTC')));
                $manager->persist($transaction);
            }
        }

        $manager->flush();
    }
}
