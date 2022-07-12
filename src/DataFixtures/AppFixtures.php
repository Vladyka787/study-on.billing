<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\User;
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
        $userTest->setEmail('notWorkUser.ru');
        $hash = $hashPassword->hashPassword($userTest, 'P');
        $userTest->setPassword($hash);

        $manager->persist($userOne);
        $manager->persist($userTwo);
        $manager->persist($userTest);

        $manager->flush();

        $this->paymentService->topUpYourAccount($userOne, null, true);
        $this->paymentService->topUpYourAccount($userTwo, null, true);
        $this->paymentService->topUpYourAccount($userTest, 100000);


        $courseDataCharacterCode = ["kursy_po_strizhke",
            "kursy_po_begu",
            "kursy_po_plavaniyu",
            "kursy_po_pryzhkam"];

        $courseDataType = ["rent",
            "rent",
            "buy",
            "free"];

        $courseDataPrice = [499.99,
            199.50,
            750.66];

        for ($i = 0; $i <= 3; $i++) {
            $course = new Course();
            $course->setCharacterCode($courseDataCharacterCode[$i]);
            $course->setType($courseDataType[$i]);
            if (array_key_exists($i, $courseDataPrice)) {
                $course->setPrice($courseDataPrice[$i]);
            }
            $manager->persist($course);
        }

        $manager->flush();

        $courses = $this->doctrine->getRepository(Course::class)->findAll();

        foreach ($courses as $course) {
            $this->paymentService->paymentCourse($userTest, $course);
        }
    }
}
