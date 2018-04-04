<?php

namespace App\Kernel;

use App\Middleware\AuthenticationMiddleware;
use App\Service\Authenticator\AuthenticatorInterface;
use Dotenv\Dotenv;
use Slim\App;
use Slim\Middleware\Session;
use Slim\Views\Twig;

/**
 * Class AppFactory
 *
 * @package App\Kernel
 */
class AppFactory
{

    /**
     * Create Slim application instance.
     *
     * @return App
     */
    public static function create(): App
    {
        $rootPath = __DIR__ . '/../../';

        //
        // Load environment specific options.
        //
        $dotenv = new Dotenv($rootPath);
        $dotenv->load();

        $debug = strtolower((string) getenv('DEBUG')) === 'true';

        $container = ContainerFactory::create($rootPath, $debug);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }

        $app = new App($container);

        $app = self::registerMiddlewares($app);
        $app = Routes::registerRoutes($app);

        return $app;
    }

    /**
     * @param App $app A slim application instance.
     *
     * @return App
     */
    private static function registerMiddlewares(App $app): App
    {
        $container = $app->getContainer();

        /** @var AuthenticatorInterface $authenticator */
        $authenticator = $container->get(AuthenticatorInterface::class);

        /** @var Twig $view */
        $view = $container->get('view');

        /** @var array{session: array} $settings */
        $settings = $container->get('settings');

        if (! isset($settings['session'])) {
            throw new \RuntimeException('Settings should contain section "session"');
        }

        return $app
            ->add(new AuthenticationMiddleware($authenticator, $view))
            ->add(new Session($settings['session']));
    }
}
