<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require_once dirname(__DIR__).'/vendor/autoload.php';

new Dotenv('APP_ENV', 'APP_DEBUG')->bootEnv(dirname(__DIR__).'/.env.test');

// Force l'environnement test et debug à 0
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = 0;
$_ENV['APP_DEBUG'] = 0;

// Clean up from previous runs
try {
    new Filesystem()->remove([__DIR__ . '/../var/cache/test']);
} catch (\Exception $e) {
    echo "Erreur suppression cache : " . $e->getMessage() . PHP_EOL;
}
new Filesystem()->remove([__DIR__ . '/../var/sessions/test']);

$releaseTestDatabaseConnections = static function (): void {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');

    if (!is_string($databaseUrl) || '' === $databaseUrl) {
        return;
    }

    $parts = parse_url($databaseUrl);
    if (false === $parts || !isset($parts['host'], $parts['path'], $parts['user'])) {
        return;
    }

    $baseDatabaseName = ltrim((string) $parts['path'], '/');
    if ('' === $baseDatabaseName) {
        return;
    }

    $testToken = (string) ($_ENV['TEST_TOKEN'] ?? $_SERVER['TEST_TOKEN'] ?? getenv('TEST_TOKEN') ?: '');
    $testDatabaseName = sprintf('%s_test%s', $baseDatabaseName, $testToken);
    $maintenanceDatabaseName = $baseDatabaseName === $testDatabaseName ? 'postgres' : $baseDatabaseName;

    try {
        $connection = new \PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $parts['host'],
                $parts['port'] ?? 5432,
                $maintenanceDatabaseName
            ),
            rawurldecode((string) $parts['user']),
            rawurldecode((string) ($parts['pass'] ?? ''))
        );

        // DBeaver and other GUI clients keep sessions open on app_test, which blocks DROP DATABASE.
        $statement = $connection->prepare(
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = :database AND pid <> pg_backend_pid()'
        );
        $statement->execute(['database' => $testDatabaseName]);
    } catch (\Throwable $exception) {
        echo sprintf("Warning: could not release test database connections: %s\n", $exception->getMessage());
    }
};

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$output = new ConsoleOutput();
$application = new Application($kernel);
$application->setAutoExit(false);
$application->setCatchExceptions(false);

$runCommand = static function (string $name, array $options = []) use ($application): void {
    $input = new ArrayInput(array_merge(['command' => $name], $options));
    $input->setInteractive(false);
    $application->run($input);
};

$releaseTestDatabaseConnections();
$runCommand('doctrine:database:drop', [
    '--force' => true,
    '--if-exists' => true,
]);
$runCommand('doctrine:database:create', [
    '--if-not-exists' => true,
]);
$runCommand('doctrine:migrations:migrate', [
    '--no-interaction' => true,
]);
$runCommand('doctrine:fixtures:load', [
    '--group' => ['CodeceptionFixtures'],
    '--no-interaction' => true,
]);

$kernel->shutdown();
