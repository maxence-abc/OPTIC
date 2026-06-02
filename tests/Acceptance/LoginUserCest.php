<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

final class LoginUserCest extends AbstractAcceptanceCest
{
    public function testTryTestUserAccountWithClientLoginValid(AcceptanceTester $I): void
    {
        $I->wantTo('test the modify user informations');

        $this->loginAsFixtureClient($I);

        $I->wait(2);
        $I->seeInCurrentUrl('/');
    }

    public function testTryTestUserAccountWithLoginInvalid(AcceptanceTester $I): void
    {
        $I->wantTo('refuse login with invalid credentials');

        $I->amOnPage('/login');
        $this->submitLoginForm($I, 'jean@gmail.com', 'azertyuiop62!');

        // Attendre que la page réagisse (évite les faux positifs)
        $I->waitForElementVisible('form', 5);

        $I->seeInCurrentUrl('/login');
    }
}
