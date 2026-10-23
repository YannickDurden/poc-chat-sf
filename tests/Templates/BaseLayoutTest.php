<?php

namespace App\Tests\Templates;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class BaseLayoutTest extends KernelTestCase
{
    public function testHeaderShowsUsernameAndLogoutButton(): void
    {
        $container = static::getContainer();
        // csrf_token() stores the token in the session, so the request needs one.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        $user = $container->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier('user');
        $container->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $html = $container->get('twig')->render('base.html.twig');

        self::assertStringContainsString('user', $html);
        self::assertStringContainsString('<form method="post" action="/logout">', $html);
        self::assertStringContainsString('name="_csrf_token"', $html);
        self::assertStringContainsString('<button type="submit">Se déconnecter</button>', $html);
    }
}
