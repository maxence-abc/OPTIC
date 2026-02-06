<?php

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;


class LoginUserCest //extends AbstractAcceptanceCest
{
    public function _before(AcceptanceTester $I)
    {
        // Exécute la commande pour charger les fixtures dans l'environnement de test
       // $I->runShellCommand('php bin/console doctrine:fixtures:load --group=CodeceptionFixtures --env=test --no-interaction');
    }


    public function testTryTestUserAccountWithClientLoginValid(AcceptanceTester $I)
    {
        $I->wantTo('test the modify user informations');

        $I->amOnPage('/login');


        // Remplir le formulaire de connexion
        $I->fillField('email', 'maxence@gmail.com');
        $I->fillField('password', 'Azertyuiop24!');

        // Soumettre le formulaire
        $I->click('Se connecter');

        $I->wait(2);
        $I->seeInCurrentUrl('/');
    }

        public function testTryTestUserAccountWithLoginInvalid(AcceptanceTester $I)
    {
        $I->wantTo('refuse login with invalid credentials');

        $I->amOnPage('/login');

        $I->fillField('email', 'jean@gmail.com');
        $I->fillField('password', 'azertyuiop62!');

        $I->click('Se connecter');

        // Attendre que la page réagisse (évite les faux positifs)
        $I->waitForElementVisible('form', 5);

        $I->seeInCurrentUrl('/login');

    }

}