<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
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
use Symfony\Component\Form\Exception\RuntimeException;
use Nelmio\ApiDocBundle\Annotation\Model;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/v1/register", name="app_api_v1_register", methods={"POST"})
     *
     * @OA\Post(
     *     description="Добавить нового пользователя и получить JWT токен",
     *     tags={"register"},
     *     @OA\RequestBody(
     *          @Model(type=UserDto::class)
     *     ),
     *     @OA\Response(
     *          response=201,
     *          description="JWT токен",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *          )
     *     )
     * )
     */
    public function register(
        Request                  $request,
        ValidatorInterface       $validator,
        JWTTokenManagerInterface $JWTManager,
        UserRepository           $userRepository
    ): JsonResponse
    {
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
     * @Route("/api/v1/users/current", name="api_v1_users_current", methods={"GET"}),
     *
     * @OA\Get(
     *     description="Получить пользователя по JWT токену",
     *     tags={"user"},
     *     )
     * @OA\Response(
     *          response=200,
     *          description="Данные пользователя",
     *          @OA\JsonContent(
     *              schema="CurrentUser",
     *              type="object",
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(
     *                  property="roles",
     *                  type="array",
     *                  @OA\Items(type="string")
     *              ),
     *              @OA\Property(property="balance", type="float")
     *          )
     *     )
     * ),
     * @Security(name="Bearer")
     * @throws JWTDecodeFailureException
     */
    public function getCurrentUser(
        Request                  $request,
        JWTTokenManagerInterface $JWTManager,
        TokenStorageInterface    $storage,
        UserRepository           $userRepository
    ): JsonResponse
    {
        $jwt = (array)$JWTManager->decode($storage->getToken());
        $array = [];
        $array['username'] = $jwt['username'];
        $array['roles'] = $jwt['roles'];

        $user = $userRepository->findOneBy(['email' => $jwt['username']]);
        $array['balance'] = $user->getBalance();

        return new JsonResponse($array, 200);
    }

    /**
     * @Route("/api/v1/auth", name="api_v1_auth", methods={"POST"})
     * @OA\Post(
     *     description="Получить JWT токен",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *          @Model(type=UserDto::class)
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="JWT токен",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string")
     *          )
     *     )
     * )
     */
    public function login(): JsonResponse
    {
        throw new RuntimeException();
    }
}
