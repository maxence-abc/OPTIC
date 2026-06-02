<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

final class ClientAccountJourneyCest extends AbstractAcceptanceCest
{
    public function testClientCanRegisterEditProfileAndLoginAgain(AcceptanceTester $I): void
    {
        $updatedEmail = $this->createUniqueEmail('acceptance-journey-updated');

        $I->wantTo('complete a full client account journey');

        $account = $this->registerClientAccount($I, [
            'email' => $this->createUniqueEmail('acceptance-journey'),
        ]);

        $I->wait(2);
        $I->seeInCurrentUrl('/login');

        $I->amOnPage('/account?tab=profile&edit=1');
        $I->seeInCurrentUrl('/login');

        $this->submitLoginForm($I, $account['email'], $account['password']);

        $I->wait(2);
        $I->seeInCurrentUrl('/account');
        $I->seeInCurrentUrl('tab=profile');
        $I->seeInCurrentUrl('edit=1');
        $I->see('Informations personnelles');
        $I->seeElement('input[name="account_profile[firstName]"]');

        $I->fillField('account_profile[firstName]', 'Juliette');
        $I->fillField('account_profile[lastName]', 'Martin-Dupont');
        $I->fillField('account_profile[email]', $updatedEmail);
        $I->fillField('account_profile[phone]', '0708091011');
        $I->click('Enregistrer');

        $I->wait(2);
        $I->seeInCurrentUrl('/account?tab=profile');
        $I->see('Vos informations ont été mises à jour.');
        $I->see('Juliette Martin-Dupont');
        $I->see($updatedEmail);
        $I->see('0708091011');

        $I->click('Se déconnecter');

        $I->wait(2);
        $I->seeInCurrentUrl('/');

        $I->amOnPage('/login');
        $this->submitLoginForm($I, $updatedEmail, $account['password']);

        $I->wait(2);
        $I->seeInCurrentUrl('/');

        $I->amOnPage('/account?tab=profile');
        $I->see('Juliette Martin-Dupont');
        $I->see($updatedEmail);
    }
}
