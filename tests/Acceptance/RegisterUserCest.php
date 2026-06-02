<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

final class RegisterUserCest extends AbstractAcceptanceCest
{
    public function testUserCanRegisterClientAccount(AcceptanceTester $I): void
    {
        $I->wantTo('register a new client account');

        $this->registerClientAccount($I);

        $I->wait(2);
        $I->seeInCurrentUrl('/login');
        $I->see('Se connecter');
    }

    public function testRegistrationFailsWhenPasswordsDoNotMatch(AcceptanceTester $I): void
    {
        $I->wantTo('stay on registration form when passwords do not match');

        $this->registerClientAccount($I, [
            'email' => $this->createUniqueEmail('acceptance-register-invalid'),
            'passwordConfirmation' => 'Azertyuiop25!',
        ]);

        $I->waitForText('The password fields must match.', 5);
        $I->seeInCurrentUrl('/registration');
        $I->see('The password fields must match.');
    }
}
