<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\Persistence\ManagerRegistry;
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
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;

const MONTH = 2592000;

class ApiController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

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
     *          description="JWT токены",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string"),
     *          )
     *     )
     * )
     */
    public function register(
        Request                        $request,
        ValidatorInterface             $validator,
        JWTTokenManagerInterface       $JWTManager,
        UserRepository                 $userRepository,
        RefreshTokenManagerInterface   $refreshTokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        PaymentService                 $paymentService
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

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, MONTH);
        $refreshToken->setUsername($user->getEmail());
        $refreshToken->setRefreshToken();
        $refreshToken->setValid((new \DateTime())->modify('+1 month'));
        $refreshTokenManager->save($refreshToken);

        $userRepository->add($user, true);

        $paymentService->topUpYourAccount($user, null, true);

        return new JsonResponse(
            [
                'token' => $JWTManager->create($user),
                'refresh_token' => $refreshToken->getRefreshToken()
            ],
            201
        );
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
     *          description="JWT токены",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string")
     *          )
     *     )
     * )
     */
    public function login(): JsonResponse
    {
        throw new RuntimeException();
    }

    /**
     * @Route("/api/v1/token/refresh", name="api_v1_refresh_token", methods={"POST"})
     *
     * @OA\Post(
     *     description="Обновить token и refresh_token",
     *     tags={"refresh"},
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="refresh_token", type="string"),
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="JWT токены",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token", type="string"),
     *              @OA\Property(property="refresh_token", type="string"),
     *          )
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        throw new RuntimeException();
    }

    /**
     * @Route("/api/v1/courses", name="api_v1_courses", methods={"GET"})
     *
     * @OA\Get(
     *     description="Получить список курсов",
     *     tags={"course"},
     *     )
     * @OA\Response(
     *          response=200,
     *          description="Все курсы",
     *          @OA\JsonContent(
     *              schema="AllCourses",
     *              type="object",
     *              @OA\Property(property="code", type="string"),
     *              @OA\Property(property="type", type="string"),
     *              @OA\Property(property="price", type="float")
     *          )
     *     )
     * )
     */
    public function getCourses(): JsonResponse
    {
        $courseRepository = $this->doctrine->getRepository(Course::class);

        $json = $courseRepository->getDataAllCourses();

        return new JsonResponse($json, 200);
    }

    /**
     * @Route("/api/v1/courses/{code}", name="api_v1_courses_code", methods={"GET"})
     *
     * @OA\Get(
     *     description="Получить курс по коду",
     *     tags={"course"},
     *     )
     * @OA\Response(
     *          response=200,
     *          description="Все курсы",
     *          @OA\JsonContent(
     *              schema="Course",
     *              type="object",
     *              @OA\Property(property="code", type="string"),
     *              @OA\Property(property="type", type="string"),
     *              @OA\Property(property="price", type="float")
     *          )
     *     )
     * )
     */
    public function getCourse(
        Request $request
    ): JsonResponse
    {
        $code = $request->get('code');

        $courseRepository = $this->doctrine->getRepository(Course::class);

        $json = $courseRepository->getCourseDataByCharacterCode($code);

        return new JsonResponse($json, 200);
    }

    /**
     * @Route("/api/v1/courses/{code}/pay", name="api_v1_courses_code_pay", methods={"POST"})
     * @OA\Post(
     *     description="Оплатить курс",
     *     tags={"course"},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="course character code",
     *         required=true,
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Данные об успешной покупке",
     *          @OA\JsonContent(
     *              schema="SuccessPay",
     *              type="object",
     *              @OA\Property(property="success", type="bool"),
     *              @OA\Property(property="course_type", type="string"),
     *              @OA\Property(property="expires_at",
     *     type="string",
     *     pattern="[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}T[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}(\+|-)[0-9]{1,2}:[0-9]{1,2}",
     *     example="2019-05-20T13:46:07+00:00")
     *          )
     *     ),
     *     @OA\Response(
     *          response=406,
     *          description="Данные об ошибке",
     *          @OA\JsonContent(
     *              schema="FailPay",
     *              type="object",
     *              @OA\Property(property="code", type="int", example="406"),
     *              @OA\Property(property="message", type="string", example="На вашем счету недостаточно средств"),
     *          )
     *     )
     * ),
     * ),
     * @Security(name="Bearer")
     */
    public function payCourse(
        Request                  $request,
        PaymentService           $paymentService,
        TransactionRepository    $transactionRepository,
        JWTTokenManagerInterface $JWTManager,
        TokenStorageInterface    $storage
    ): JsonResponse
    {
        $code = $request->get('code');
        $jwt = (array)$JWTManager->decode($storage->getToken());

        $username = $jwt['username'];

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $username]);
        $course = $this->doctrine->getRepository(Course::class)->findOneBy(['CharacterCode' => $code]);

//        Is the course purchased?
        $filter = [];
        $filter['type'] = 'payment';
        $filter['course_code'] = $course->getCharacterCode();
        $filter['skip_expired'] = true;

        $check = $transactionRepository->getAllTransactionByUser($user, $filter);

        if ($check !== []) {
            $json = [];
            $json['code'] = 406;
            $json['message'] = 'Данный курс уже приобретен вами';
            return new JsonResponse($json, 406);
        }

        if ($course->getType() === 'free') {
            $json = [];
            $json['code'] = 406;
            $json['message'] = 'Данный курс бесплатный';
            return new JsonResponse($json, 406);
        }


        if ($paymentService->paymentCourse($user, $course)) {
            $json = [];
            $json['success'] = true;
            $json['course_type'] = $course->getType();
            $json['expires_at'] = $transactionRepository->getExpiresDateByUserAndCharacterCode($user, $course);
        } else {
            $json = [];
            $json['code'] = 406;
            $json['message'] = 'На вашем счету недостаточно средств';
            return new JsonResponse($json, 406);
        }

        return new JsonResponse($json, 200);
    }

    /**
     * @Route("/api/v1/transactions", name="api_v1_transactions", methods={"GET"})
     *
     * @OA\Get(
     *     description="Получить данные о транзакциях",
     *     tags={"transaction"},
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         required=false,
     *         description="payment|deposit",
     *         @OA\Property(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[course_code]",
     *         in="query",
     *         required=false,
     *         description="Символьный код курса",
     *         @OA\Property(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[skip_expired]",
     *         in="query",
     *         required=false,
     *         description="Позволяет убрать записи с арендой, которая закончилась",
     *         @OA\Property(type="boolean")
     *     ),
     *     ),
     * @OA\Response(
     *          response=200,
     *          description="Транзакции подходящие под условия",
     *          @OA\JsonContent(
     *              schema="transaction",
     *              type="object",
     *              @OA\Property(property="id", type="int"),
     *              @OA\Property(property="created_at",
     *              type="string",
     *              pattern="[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}T[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}(\+|-)[0-9]{1,2}:[0-9]{1,2}",
     *              example="2019-05-20T13:46:07+00:00"
     *              ),
     *              @OA\Property(property="type", type="string"),
     *              @OA\Property(property="course-code", type="string"),
     *              @OA\Property(property="amount", type="float")
     *          )
     *     )
     * ),
     * @Security(name="Bearer")
     */
    public function getTransactions(
        Request                  $request,
        TransactionRepository    $transactionRepository,
        JWTTokenManagerInterface $JWTManager,
        TokenStorageInterface    $storage
    ): JsonResponse
    {
        $filter = $request->get('filter');
        $jwt = (array)$JWTManager->decode($storage->getToken());

        $username = $jwt['username'];

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $username]);

        $json = $transactionRepository->getAllTransactionByUser($user, $filter);

//        $json['test']=$filter;

        return new JsonResponse($json, 200);
    }
}
