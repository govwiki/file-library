<?php

namespace App;

use App\Action\ErrorAction;
use App\Action\IndexAction;
use App\Action\LoginAction;
use App\Action\LogoutAction;
use App\Middleware\AuthenticationMiddleware;
use App\Repository\PDO\UserPDORepository;
use App\Repository\UserRepositoryInterface;
use App\Service\Authenticator\Authenticator;
use App\Service\Authenticator\AuthenticatorInterface;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container;
use Slim\Interfaces\RouterInterface;
use Slim\Middleware\Session;
use Slim\Views\PhpRenderer;
use SlimSession\Helper;

/**
 * Class Core
 *
 * @package App
 */
class Core
{

    const SECURITY_USER_KEY = '_user';

    /**
     * @var App
     */
    private $app;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        $rootPath = __DIR__ . '/../';

        //
        // Load environment specific options.
        //
        $dotenv = new Dotenv($rootPath);
        $dotenv->load();

        $this->container = new Container([
            'settings' => [
                'displayErrorDetails' => ! getenv('DEBUG'),
                'addContentLengthHeader' => false,
                'renderer' => [
                    'template_path' => $rootPath . '/templates/',
                ],
                'debug' => getenv('DEBUG'),
                'db' => [
                    'host' => getenv('DB_HOST'),
                    'port' => getenv('DB_PORT'),
                    'name' => getenv('DB_NAME'),
                    'user' => getenv('DB_USER'),
                    'password' => getenv('DB_PASSWORD'),
                ],
                'session' => [
                    'name' => 'session',
                    'lifetime' => '24 hour',
                    'autorefresh' => true,
                ],
            ],
        ]);

        //
        // Setup slim framework.
        //
        $this->app = new App($this->container);

        $this->registerServices();
        $this->registerRoutes();
        $this->registerMiddlewares();

        $this->app->run();
    }

    /**
     * Register application middleware's.
     *
     * @return void
     */
    private function registerMiddlewares()
    {
        $this->app
            ->add(new AuthenticationMiddleware($this->container[AuthenticatorInterface::class]))
            ->add(new Session($this->container['settings']['session']));
    }

    /**
     * Register application container and services.
     *
     * @return ContainerInterface
     */
    private function registerServices(): ContainerInterface
    {
        //
        // Register application services.
        //

        $this->container['errorHandler'] = function (ContainerInterface $container): ErrorAction {
            /** @var PhpRenderer $renderer */
            $renderer = $container->get(PhpRenderer::class);

            return new ErrorAction($renderer, 'error');
        };
        $this->container['notFoundHandler'] = function (ContainerInterface $container): ErrorAction {
            /** @var PhpRenderer $renderer */
            $renderer = $container->get(PhpRenderer::class);

            return new ErrorAction($renderer, 'error');
        };

        /**
         * Register session instance.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return Helper
         */
        $this->container['session'] = function (ContainerInterface $container) {
            return new Helper();
        };

        /**
         * Register view renderer.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return PhpRenderer
         */
        $this->container[PhpRenderer::class] = function (ContainerInterface $container) {
            $settings = $container->get('settings')['renderer'];

            return new PhpRenderer($settings['template_path']);
        };

        /**
         * Register database connection.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return \PDO
         */
        $this->container[\PDO::class] = function (ContainerInterface $container) {
            $settings = $container->get('settings')['db'];

            $dsn = sprintf('mysql:host=%s;dbname=%s', $settings['host'], $settings['name']);
            if ($settings['port'] !== '') {
                $dsn .= 'port='. $settings['port'];
            }

            return new \PDO($dsn, $settings['user'], $settings['password'], [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ]);
        };

        /**
         * Register user repository.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return UserRepositoryInterface
         */
        $this->container[UserRepositoryInterface::class] = function (ContainerInterface $container) {
            /** @var \PDO $renderer */
            $pdo = $container->get(\PDO::class);

            return new UserPDORepository($pdo);
        };

        /**
         * Register authenticator.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return Authenticator
         */
        $this->container[AuthenticatorInterface::class] = function (ContainerInterface $container) {
            /** @var UserRepositoryInterface $repository */
            $repository = $container->get(UserRepositoryInterface::class);
            /** @var Helper $session */
            $session = $container->get('session');

            return new Authenticator($repository, $session);
        };

        //
        // Register actions.
        //
        $this->container[IndexAction::class] = function (ContainerInterface $container) {
            /** @var PhpRenderer $renderer */
            $renderer = $container->get(PhpRenderer::class);

            return new IndexAction($renderer);
        };

        $this->container[LoginAction::class] = function (ContainerInterface $container) {
            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $container->get(AuthenticatorInterface::class);
            /** @var PhpRenderer $renderer */
            $renderer = $container->get(PhpRenderer::class);
            /** @var RouterInterface $router */
            $router = $container->get('router');

            return new LoginAction($authenticator, $renderer, $router);
        };

        $this->container[LogoutAction::class] = function (ContainerInterface $container) {
            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $container->get(AuthenticatorInterface::class);
            /** @var RouterInterface $router */
            $router = $container->get('router');

            return new LogoutAction($authenticator, $router);
        };

        return $this->container;
    }

    /**
     * Setup application routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $this->app->get('/', IndexAction::class)->setName('main');

        $this->app->map([ 'GET', 'POST' ], '/login', LoginAction::class)->setName('login');
        $this->app->get('/logout', LogoutAction::class)->setName('logout');
    }
}
