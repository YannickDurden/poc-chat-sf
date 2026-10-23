<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testAnonymousCanSeeRegistrationForm(): void
    {
        $this->client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Créer un compte');
        self::assertSelectorExists('input[name="registration_form[username]"]');
        self::assertSelectorExists('input[type="password"][name="registration_form[plainPassword][first]"]');
        self::assertSelectorExists('input[type="password"][name="registration_form[plainPassword][second]"]');
        self::assertSelectorExists('select[name="registration_form[role]"] option[value="ROLE_USER"]');
        self::assertSelectorExists('select[name="registration_form[role]"] option[value="ROLE_ADMIN"]');
        self::assertSelectorExists('input[type="hidden"][name="registration_form[_token]"]');
    }

    public function testRegisteredAdminCanLogIn(): void
    {
        $this->submitRegistration('alice', 'motdepasse', 'motdepasse', 'ROLE_ADMIN');

        self::assertResponseRedirects('/login');
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'alice']);
        self::assertNotNull($user);
        self::assertSame(UserRole::ADMIN, $user->getRole());
        self::assertNotSame('motdepasse', $user->getPassword());

        $this->client->followRedirect();
        self::assertSelectorTextContains('.form-success', 'Compte créé, vous pouvez vous connecter.');

        $this->client->submitForm('Se connecter', ['_username' => 'alice', '_password' => 'motdepasse']);
        self::assertResponseRedirects('/chat');
    }

    public function testMismatchedPasswordsShowError(): void
    {
        $this->submitRegistration('alice', 'motdepasse', 'autrechose', 'ROLE_USER');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Les mots de passe ne correspondent pas.');
        self::assertSame(0, $this->getEntityManager()->getRepository(User::class)->count());
    }

    public function testDuplicateUsernameShowsError(): void
    {
        $user = new User();
        $user->setUsername('alice');
        $user->setPassword('hash');
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $this->submitRegistration('alice', 'motdepasse', 'motdepasse', 'ROLE_USER');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Cet identifiant est déjà utilisé.');
        self::assertSame(1, $entityManager->getRepository(User::class)->count());
    }

    public function testReservedUsernameShowsError(): void
    {
        $this->submitRegistration('admin', 'motdepasse', 'motdepasse', 'ROLE_ADMIN');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Cet identifiant est déjà utilisé.');
        self::assertSame(0, $this->getEntityManager()->getRepository(User::class)->count());
    }

    private function submitRegistration(string $username, string $password, string $confirmation, string $role): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer le compte', [
            'registration_form[username]' => $username,
            'registration_form[plainPassword][first]' => $password,
            'registration_form[plainPassword][second]' => $confirmation,
            'registration_form[role]' => $role,
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
