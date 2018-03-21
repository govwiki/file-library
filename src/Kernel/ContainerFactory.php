<?php

namespace App\Kernel;

use App\Action\ErrorHandler;
use App\Controller\DocumentController;
use App\Controller\SecurityController;
use App\Repository\DocumentRepositoryInterface;
use App\Repository\PDO\DocumentPDORepository;
use App\Repository\PDO\UserPDORepository;
use App\Repository\UserRepositoryInterface;
use App\Service\Authenticator\Authenticator;
use App\Service\Authenticator\AuthenticatorInterface;
use Psr\Container\ContainerInterface;
use Slim\Container;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use SlimSession\Helper;

/**
 * Class ContainerFactory
 *
 * @package App\Kernel
 */
class ContainerFactory
{

    /**
     * @param string  $rootPath Absolute path to application root directory.
     * @param boolean $debug    Create container in debug mode.
     *
     * @return ContainerInterface
     */
    public static function create(string $rootPath, bool $debug = false): ContainerInterface
    {
        $path = realpath($rootPath);
        if (! is_string($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid application root path \'%s\'',
                $rootPath
            ));
        }

        $container = new Container([
            'settings' => [
                'displayErrorDetails' => $debug,
                'addContentLengthHeader' => false,
                'view' => [
                    'template_path' => $path . '/templates/',
                    'cache_path' => $path . '/var/cache/twig',
                    'debug' => $debug,
                ],
                'debug' => $debug,
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

        $container = self::registerServices($container);
        $container = self::registerControllers($container);

        return $container;
    }

    /**
     * @param ContainerInterface $container Application container.
     *
     * @return ContainerInterface
     */
    private static function registerServices(ContainerInterface $container): ContainerInterface
    {
        //
        // Register application services.
        //
        /** @psalm-suppress MixedArrayAccess */
        if ($container['settings']['debug'] === false) {
            $container['errorHandler'] = function (ContainerInterface $container): ErrorHandler {
                /** @var Twig $renderer */
                $renderer = $container->get(Twig::class);

                return new ErrorHandler($renderer, 'error');
            };
            $container['notFoundHandler'] = function (ContainerInterface $container): ErrorHandler {
                /** @var Twig $renderer */
                $renderer = $container->get(Twig::class);

                return new ErrorHandler($renderer, 'error');
            };
        }

        /**
         * Register session instance.
         *
         * @return Helper
         */
        $container['session'] = function (): Helper {
            return new Helper();
        };

        /**
         * Register view renderer.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return Twig
         */
        $container['view'] = function (ContainerInterface $container): Twig {
            /** @var array{template_path: string, debug: boolean, cache_path: string} $settings */
            $settings = self::getSettings($container, 'view', [
                'template_path',
                'debug',
                'cache_path',
            ]);

            $view = new Twig($settings['template_path'], [
                'auto_reload' => $settings['debug'],
                'cache' => $settings['cache_path'],
                'debug' => $settings['debug'],
            ]);

            $view->addExtension(new TwigExtension($container['router'], '/'));
            $view->addExtension(new \App\Twig\Extension\Twig());

            return $view;
        };

        /**
         * Register database connection.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return \PDO
         */
        $container[\PDO::class] = function (ContainerInterface $container): \PDO {
            /** @var array{host: string, name: string, user: string, password: string, port: string} $settings */
            $settings = self::getSettings($container, 'db', [
                'host',
                'name',
                'user',
                'password',
                'port',
            ]);
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
        $container[UserRepositoryInterface::class] = function (ContainerInterface $container): UserRepositoryInterface {
            /** @var \PDO $pdo */
            $pdo = $container->get(\PDO::class);

            return new UserPDORepository($pdo);
        };

        /**
         * Register document repository.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return DocumentRepositoryInterface
         */
        $container[DocumentRepositoryInterface::class] = function (ContainerInterface $container): DocumentRepositoryInterface {
            /** @var \PDO $pdo */
            $pdo = $container->get(\PDO::class);

            return new DocumentPDORepository($pdo);
        };

        /**
         * Register authenticator.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return AuthenticatorInterface
         */
        $container[AuthenticatorInterface::class] = function (ContainerInterface $container): AuthenticatorInterface {
            /** @var UserRepositoryInterface $repository */
            $repository = $container->get(UserRepositoryInterface::class);
            /** @var Helper $session */
            $session = $container->get('session');

            return new Authenticator($repository, $session);
        };

        return $container;
    }

    /**
     * @param ContainerInterface $container Application container.
     *
     * @return ContainerInterface
     */
    private static function registerControllers(ContainerInterface $container): ContainerInterface
    {
        /**
         * Document controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return DocumentController
         */
        $container[DocumentController::class] = function (ContainerInterface $container): DocumentController {
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var DocumentRepositoryInterface $repository */
            $repository = $container->get(DocumentRepositoryInterface::class);

            return new DocumentController($view, $repository);
        };

        /**
         * SecurityController controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return SecurityController
         */
        $container[SecurityController::class] = function (ContainerInterface $container): SecurityController {
            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $container->get(AuthenticatorInterface::class);
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var RouterInterface $router */
            $router = $container->get('router');

            return new SecurityController($authenticator, $view, $router);
        };

        return $container;
    }

    /**
     * @param ContainerInterface $container       A ContainerInterface instance.
     * @param string             $section         Required settings section name.
     * @param array              $requiredOptions Required section options.
     *
     * @return array
     */
    private static function getSettings(
        ContainerInterface $container,
        string $section,
        array $requiredOptions = []
    ): array {
        /** @var array $settings */
        $settings = $container->get('settings');

        if (! isset($settings[$section])) {
            throw new \RuntimeException(sprintf(
                'Settings should contains "%s" section',
                $section
            ));
        }

        /** @var array<string, mixed> $options */
        $options = $settings[$section];

        array_walk($requiredOptions, function ( string $name) use ($section, $options) {
            if (! isset($options[$name])) {
                throw new \RuntimeException(sprintf(
                    'Settings section "%s" should contains %s options',
                    $section,
                    implode(', ', $options)
                ));
            }
        });

        return $options;
    }
}
