<?php

namespace App\Kernel;

use App\Kernel\Container\ContainerControllersFactory;
use App\Kernel\Container\ContainerServicesFactory;
use Psr\Container\ContainerInterface;
use Slim\Container;

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
        if (! \is_string($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid application root path \'%s\'',
                $rootPath
            ));
        }

        $container = new Container([
            'settings' => [
                'domain' => getenv('DOMAIN'),
                'displayErrorDetails' => $debug,
                'addContentLengthHeader' => false,
                'fileStorage' => [
                    'root' => getenv('FILE_STORAGE_ROOT'),
                ],
                'view' => [
                    'template_path' => $path . '/templates/',
                    'cache_path' => $path . '/var/cache/twig',
                    'debug' => $debug,
                ],
                'debug' => $debug,
                'doctrine' => [
                    'meta' => [
                        'entity_path' => [ $path . '/src/Entity' ],
                        'auto_generate_proxies' => true,
                        'proxy_dir' => $path . '/var/cache/proxies',
                        'cache' => null,
                    ],
                    'connection' => [
                        'driver' => 'pdo_mysql',
                        'host' => getenv('DB_HOST'),
                        'port' => getenv('DB_PORT'),
                        'dbname' => getenv('DB_NAME'),
                        'user' => getenv('DB_USER'),
                        'password' => getenv('DB_PASSWORD'),
                    ],
                ],
                'session' => [
                    'name' => 'session',
                    'lifetime' => '24 hour',
                    'autorefresh' => true,
                ],
            ],
        ]);

        ContainerServicesFactory::register($container);
        ContainerControllersFactory::register($container);

        return $container;
    }
}
