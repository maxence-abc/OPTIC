<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;
use Throwable;

abstract class AbstractAcceptanceCest
{
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
            $I->amOnPage('/login');
        } catch (Throwable $exception) {
            $I->comment(sprintf('Logout skipped: %s', $exception->getMessage()));
        }
    }
}