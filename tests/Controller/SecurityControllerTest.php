<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testAnonymousChatRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/chat');

        self::assertResponseRedirects('/login');
    }

    public function testAnonymousAdminRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/chat');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageRendersForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testValidCredentialsRedirectToChat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', ['_username' => 'user', '_password' => 'user']);

        self::assertResponseRedirects('/chat');
    }

    public function testInvalidCredentialsShowError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', ['_username' => 'user', '_password' => 'wrong']);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorTextContains('.form-error', 'Identifiant ou mot de passe invalide.');
        self::assertInputValueSame('_username', 'user');
    }

    public function testUserCannotAccessAdmin(): void
    {
        $client = static::createClient();
        // The token user must match the provider's one (password hash included), or the firewall logs it out on refresh.
        $user = static::getContainer()->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier('user');
        $client->loginUser($user);
        $client->request('GET', '/admin/chat');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousHeaderShowsAppNameOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertSelectorTextContains('header.site-header', 'POC Chat');
        self::assertSelectorNotExists('header.site-header button');
    }

    public function testCsrfFieldsLoadTheStatelessCsrfScript(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        // Stateless CSRF needs csrf_protection_controller.js, which is lazy-loaded through this attribute.
        self::assertSelectorExists('.login-form input[name="_csrf_token"][data-controller="csrf-protection"]');

        $user = static::getContainer()->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier('user');
        $client->loginUser($user);
        $client->request('GET', '/register');

        self::assertSelectorExists('header.site-header input[name="_csrf_token"][data-controller="csrf-protection"]');
    }

    public function testLogoutRejectsGet(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier('user');
        $client->loginUser($user);
        $client->request('GET', '/logout');

        self::assertResponseStatusCodeSame(405);
    }

    public function testLogoutRejectsInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier('user');
        $client->loginUser($user);
        $client->request('POST', '/logout', ['_csrf_token' => 'invalid']);

        self::assertResponseStatusCodeSame(403);
    }
}
