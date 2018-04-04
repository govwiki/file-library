<?php

namespace App\Kernel\Container;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use App\Entity\EntityFactory;
use App\Entity\User;
use App\Repository\DirectoryRepositoryInterface;
use App\Repository\FileRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\Authenticator\Authenticator;
use App\Service\Authenticator\AuthenticatorInterface;
use App\Service\FileStorage\FileStorageInterface;
use App\Service\FileStorage\FilesystemFileStorage;
use App\Service\FileStorage\ORMIndexFileStorage;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use SlimSession\Helper;

/**
 * Class ContainerServicesFactory
 *
 * @package App\Kernel\Container
 */
class ContainerServicesFactory
{

    const REPOSITORIES_MAP = [
        FileRepositoryInterface::class => AbstractFile::class,
        DirectoryRepositoryInterface::class => Directory::class,
        UserRepositoryInterface::class => User::class,
    ];

    /**
     * @param ContainerInterface $container Application container.
     *
     * @return void
     */
    public static function register(ContainerInterface $container)
    {
        //
        // Register application services.
        //

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
            $view->addExtension(new \App\Kernel\Twig\TwigExtension());

            return $view;
        };

        /**
         * Register database connection.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return EntityManagerInterface
         */
        $container['em'] = function (ContainerInterface $container): EntityManagerInterface {
            /** @var array{meta: array, connection: array} $settings */
            $settings = self::getSettings($container, 'doctrine', [
                'meta',
                'connection',
            ]);

            /** @var array{entity_path: string[], auto_generate_proxies: bool, proxy_dir: string, cache: Cache|null} $meta */
            $meta = $settings['meta'];

            $config = Setup::createAnnotationMetadataConfiguration(
                $meta['entity_path'],
                $meta['auto_generate_proxies'],
                $meta['proxy_dir'],
                $meta['cache'],
                false
            );
            $config->setNamingStrategy(new class() extends UnderscoreNamingStrategy { // @codingStandardsIgnoreLine
                /**
                 * Underscore naming strategy construct.
                 */
                public function __construct()
                {
                    parent::__construct();
                }

                /**
                 * Returns a table name for an entity class.
                 *
                 * @param string $className The fully-qualified class name.
                 *
                 * @return string A table name.
                 */
                public function classToTableName($className): string // @codingStandardsIgnoreLine
                {
                    // Remove 'abstract_' prefix from table name.
                    $name = str_replace('abstract_', '', parent::classToTableName($className));

                    return Inflector::pluralize($name);
                }
            });

            return EntityManager::create($settings['connection'], $config);
        };

        foreach (self::REPOSITORIES_MAP as $repositoryFqcn => $entityFqcn) {
            $container[$repositoryFqcn] = function (ContainerInterface $container) use ($entityFqcn): ObjectRepository {
                /** @var EntityManagerInterface $em */
                $em = $container->get('em');

                return $em->getRepository($entityFqcn);
            };
        }

        /**
         * Create entity factory.
         *
         * @return EntityFactory
         */
        $container[EntityFactory::class] = function (ContainerInterface $container): EntityFactory {
            /** @var DirectoryRepositoryInterface $directoryRepository */
            $directoryRepository = $container->get(DirectoryRepositoryInterface::class);

            return new EntityFactory($directoryRepository);
        };

        /**
         * Create file storage instance.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return FileStorageInterface
         */
        $container[FileStorageInterface::class] = function (ContainerInterface $container): FileStorageInterface {
            /** @var EntityManagerInterface $em */
            $em = $container->get('em');
            /** @var EntityFactory $factory */
            $factory = $container->get(EntityFactory::class);

            /** @var array{root: string} $settings */
            $settings = self::getSettings($container, 'fileStorage', [ 'root' ]);

            $fileStorage = new FilesystemFileStorage($settings['root']);

            return new ORMIndexFileStorage($fileStorage, $em, $factory);
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

        array_walk($requiredOptions, function (string $name) use ($section, $options) {
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
