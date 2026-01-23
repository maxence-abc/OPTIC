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
    public function testTryTestUserAccountWithAdministrtorCompany(AcceptanceTester $I)
    {
        $I->wantTo('test the modify user informations');

        $I->amOnPage('/login');

        // Remplir le formulaire de connexion
        $I->fillField('email', 'client.client@gmail.com');
        $I->fillField('password', 'Azertyuiop24!');

        // Soumettre le formulaire
        $I->click('Se connecter');

        $I->wait(2);
        $I->seeInCurrentUrl('/');
    }
}