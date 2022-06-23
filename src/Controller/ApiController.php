<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/v1/register", name="app_api_v1_register", methods={"POST"})
     */
    public function register(
        Request                  $request,
        ValidatorInterface       $validator,
        JWTTokenManagerInterface $JWTManager,
        UserRepository           $userRepository
    ): JsonResponse {
        $email = json_decode($request->getContent());
        $email = $email->username;

        $serializer = SerializerBuilder::create()->build();

        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errors = $validator->validate($userDto);

        $repeatEmail = $userRepository->findBy(['email' => $email]);

        if ((count($errors) > 0) && ($repeatEmail)) {
            $errorsRepeat = json_encode("This email is already registered");

            return $this->json([
                'errors' => (string)$errors,
                'errorRepeat' => $errorsRepeat,
            ], 400);
        } elseif (count($errors) > 0) {
            return $this->json([
                'errors' => (string)$errors,
            ], 400);
        } elseif ($repeatEmail) {
            $errorsRepeat = json_encode("This email is already registered");

            return $this->json([
                'errorRepeat' => $errorsRepeat,
            ], 400);
        }

        $user = \App\Entity\User::fromDto($userDto);

        $passwordHasherFactory = new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto'],
        ]);
        $hashPassword = new UserPasswordHasher($passwordHasherFactory);
        $hash = $hashPassword->hashPassword($user, 'Password');
        $user->setPassword($hash);

        $userRepository->add($user, true);

        return new JsonResponse(['token' => $JWTManager->create($user)], 201);
    }

    /**
     * @Route("/api/v1/users/current", name="api_v1_users_current", methods={"GET"})
     * @throws JWTDecodeFailureException
     */
    public function getCurrentUser(
        Request                  $request,
        JWTTokenManagerInterface $JWTManager,
        TokenStorageInterface    $storage,
        UserRepository           $userRepository
    ): JsonResponse {
        $jwt = (array)$JWTManager->decode($storage->getToken());
        $array = [];
        $array['username'] = $jwt['username'];
        $array['roles'] = $jwt['roles'];

        $user = $userRepository->findOneBy(['email' => $jwt['username']]);
        $array['balance'] = $user->getBalance();

        return new JsonResponse($array, 200);
    }
}
