<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

final class LogoutUserCest extends AbstractAcceptanceCest
{
    public function testClientMustLoginAgainAfterLogout(AcceptanceTester $I): void
    {
        $I->wantTo('protect the account page again after logout');

        $account = $this->registerClientAccount($I, [
            'email' => $this->createUniqueEmail('acceptance-logout'),
        ]);

        $I->wait(2);
        $I->seeInCurrentUrl('/login');

        $I->amOnPage('/account?tab=profile');
        $I->seeInCurrentUrl('/login');
        $this->submitLoginForm($I, $account['email'], $account['password']);

        $I->wait(2);
        $I->seeInCurrentUrl('/account');
        $I->seeInCurrentUrl('tab=profile');
        $I->see('Informations personnelles');
        $I->click('Se déconnecter');

        $I->wait(2);
        $I->seeInCurrentUrl('/');

        $I->amOnPage('/account');
        $I->seeInCurrentUrl('/login');
        $I->see('Connexion');
    }
}
