<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DomCrawler\Crawler;

const URLREG = 'http://billing.study-on.local:82/api/v1/register';

class ApiControllerTest extends WebTestCase
{
    public function testRegisterAddNewUser()
    {
        $client = static::createClient();

        $id = uniqid('', false);

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => $id . "@mail.ru", "password" => "password"]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testRegisterAddOldUser()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => "userOne@mail.ru", "password" => "Password"]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterAddNewUserWithBadData()
    {
        $client = static::createClient();

        $id = uniqid('', false);

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => $id . "@mail.ru", "password" => "pass"]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterAddNewUserWithRepeatEmail()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => "userOne@mail.ru", "password" => "password"]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetCurrentUser()
    {
        $client = static::createClient();

        $id = uniqid('', false);

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => $id . "@mail.ru", "password" => "password"]);

        $this->assertResponseStatusCodeSame(201);

        $arr = json_decode($client->getResponse()->getContent());
        $token = $arr->token;

        $crawler = $client->request('GET', 'http://billing.study-on.local:82/api/v1/users/current', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testAuth()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('POST', 'http://billing.study-on.local:82/api/v1/auth', ["username" => "userOne@mail.ru", "password" => "Password"]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testAuthAndRefresh()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('POST', 'http://billing.study-on.local:82/api/v1/auth', ["username" => "userOne@mail.ru", "password" => "Password"]);

        $this->assertResponseStatusCodeSame(200);

        $arr = json_decode($client->getResponse()->getContent());
        $refresh_token = $arr->refresh_token;

        $crawler = $client->jsonRequest('POST', 'http://billing.study-on.local:82/api/v1/token/refresh', ['refresh_token' => $refresh_token]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCourses()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('GET', 'http://billing.study-on.local:82/api/v1/courses');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetConcreteCourse()
    {
        $client = static::createClient();

        $code = "kursy_po_strizhke";

        $crawler = $client->jsonRequest('GET', 'http://billing.study-on.local:82/api/v1/courses/' . $code);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testPayCourse()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest('POST', 'http://billing.study-on.local:82/api/v1/auth', ["username" => "userOne@mail.ru", "password" => "Password"]);

        $this->assertResponseStatusCodeSame(200);

        $arr = json_decode($client->getResponse()->getContent());
        $token = $arr->token;

        $code = "kursy_po_strizhke";

        $crawler = $client->request('POST', 'http://billing.study-on.local:82/api/v1/courses/' . $code . '/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request('POST', 'http://billing.study-on.local:82/api/v1/courses/' . $code . '/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(406);

        $code = "kursy_po_pryzhkam";

        $crawler = $client->request('POST', 'http://billing.study-on.local:82/api/v1/courses/' . $code . '/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(406);

        $code = "infinity_money";

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses/' . $code . '/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(406);
    }

    public function testGetTransaction()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest(
            'POST',
            'http://billing.study-on.local:82/api/v1/auth',
            [
                "username" => "userOne@mail.ru", "password" => "Password"
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $arr = json_decode($client->getResponse()->getContent());
        $token = $arr->token;

        $filter = [];
        $filter['type'] = 'payment';
        $filter['course_code'] = "kursy_po_strizhke";
        $filter['skip_expired'] = true;

        $wrapper = [];
        $wrapper['filter'] = $filter;
        $arr = http_build_query($wrapper);

        $crawler = $client->request(
            'GET',
            'http://billing.study-on.local:82/api/v1/transactions?' . $arr,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateCourse()
    {
        $client = static::createClient();

        $crawler = $client->jsonRequest(
            'POST',
            'http://billing.study-on.local:82/api/v1/auth',
            [
                "username" => "userOne@mail.ru",
                "password" => "Password"
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $arr = json_decode($client->getResponse()->getContent());
        $token = $arr->token;

        $data = [];
        $data['type'] = 'rent';
        $data['title'] = 'Новый курс';
        $data['code'] = 'new_course';
        $data['price'] = 456.55;

        $json = $data;

        $jsonStringCreate = json_encode($json, JSON_THROW_ON_ERROR);

        $characterCode = 'kursy_po_strizhke';

        $data = [];
        $data['type'] = 'rent';
        $data['title'] = 'Отредактированный курс';
        $data['code'] = 'edit_course';
        $data['price'] = 700;

        $json = $data;

        $jsonStringEdit = json_encode($json, JSON_THROW_ON_ERROR);

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses/' . $characterCode,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringEdit
        );

        $this->assertResponseStatusCodeSame(403);

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringCreate
        );

        $this->assertResponseStatusCodeSame(403);

        $crawler = $client->jsonRequest(
            'POST',
            'http://billing.study-on.local:82/api/v1/auth',
            [
                "username" => "userTwo@mail.ru",
                "password" => "SuperPassword"
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $arr = json_decode($client->getResponse()->getContent());
        $token = $arr->token;

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringCreate
        );

        $this->assertResponseStatusCodeSame(201);

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses/' . $characterCode,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringEdit
        );

        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringCreate
        );

        $this->assertResponseStatusCodeSame(406);

        $characterCode = 'not';

        $crawler = $client->request(
            'POST',
            'http://billing.study-on.local:82/api/v1/courses/' . $characterCode,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $jsonStringEdit
        );

        $this->assertResponseStatusCodeSame(406);
    }

    public function testCommand()
    {
        $kernel= self::bootKernel();
        $application = new Application($kernel);

        $command= $application->find('payment:ending:notification');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $command= $application->find('payment:report');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
