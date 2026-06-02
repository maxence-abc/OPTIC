<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;
use Throwable;

abstract class AbstractAcceptanceCest
{
    private const DEFAULT_CLIENT_EMAIL = 'maxence@gmail.com';
    private const DEFAULT_CLIENT_PASSWORD = 'Azertyuiop24!';
    private const LOGIN_ROUTE = '/login';
    private const REGISTER_ROUTE = '/registration';
    private const LOGOUT_ROUTE = '/logout';

    public function _after(AcceptanceTester $I): void
    {
        $this->logout($I);
    }

    protected function logout(AcceptanceTester $I): void
    {
        try {
            $I->amOnPage(self::LOGOUT_ROUTE);
            $I->wait(1);
            $I->amOnPage(self::LOGIN_ROUTE);
        } catch (Throwable $exception) {
            $I->comment(sprintf('Logout skipped: %s', $exception->getMessage()));
        }
    }

    protected function loginAsFixtureClient(AcceptanceTester $I): void
    {
        $I->amOnPage(self::LOGIN_ROUTE);
        $this->submitLoginForm($I, self::DEFAULT_CLIENT_EMAIL, self::DEFAULT_CLIENT_PASSWORD);
    }

    protected function submitLoginForm(AcceptanceTester $I, string $email, string $password): void
    {
        $I->fillField('email', $email);
        $I->fillField('password', $password);
        $I->click('Se connecter');
    }

    /**
     * @param array{
     *     firstName?: string,
     *     lastName?: string,
     *     email?: string,
     *     phone?: string,
     *     password?: string,
     *     passwordConfirmation?: string
     * } $overrides
     *
     * @return array{
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     phone: string,
     *     password: string,
     *     passwordConfirmation: string
     * }
     */
    protected function registerClientAccount(AcceptanceTester $I, array $overrides = []): array
    {
        $account = array_merge([
            'firstName' => 'Julie',
            'lastName' => 'Martin',
            'email' => $this->createUniqueEmail('acceptance-register'),
            'phone' => '0601020304',
            'password' => self::DEFAULT_CLIENT_PASSWORD,
            'passwordConfirmation' => self::DEFAULT_CLIENT_PASSWORD,
        ], $overrides);

        $I->amOnPage(self::REGISTER_ROUTE);
        $I->fillField('user[firstName]', $account['firstName']);
        $I->fillField('user[lastName]', $account['lastName']);
        $I->fillField('user[email]', $account['email']);
        $I->fillField('user[phone]', $account['phone']);
        $I->fillField('user[password][first]', $account['password']);
        $I->fillField('user[password][second]', $account['passwordConfirmation']);
        $I->click('Créer mon compte');

        return $account;
    }

    protected function createUniqueEmail(string $prefix): string
    {
        return sprintf('%s-%s@test.local', $prefix, uniqid('', true));
    }
}
