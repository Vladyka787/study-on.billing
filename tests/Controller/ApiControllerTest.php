<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

const URLREG = 'http://billing.study-on.local:82/api/v1/register';

class ApiControllerTest extends WebTestCase
{
    public function testRegisterAddNewUser()
    {
        $client = static::createClient();

        $id = uniqid('', false);

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => $id . "@mail.ru", "password" => $id . "password"]);

//        $vrem = $client->getRequest()->getContent();

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

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => "44@mail.ru", "password" => "password"]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetCurrentUser()
    {
        $client = static::createClient();

        $id = uniqid('', false);

        $crawler = $client->jsonRequest('POST', URLREG, ["username" => $id . "@mail.ru", "password" => $id . "password"]);

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
}
