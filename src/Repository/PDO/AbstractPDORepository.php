<?php

namespace App\Repository\PDO;

use Assert\Assertion;

/**
 * Class AbstractPDORepository
 *
 * @package App\Repository\PDO
 */
abstract class AbstractPDORepository
{

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * AbstractPDORepository constructor.
     *
     * @param \PDO $pdo A PDO instance.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $sql    Executed query.
     * @param array  $params Query params.
     *
     * @return \PDOStatement
     */
    protected function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new \PDOException(sprintf(
                'Can\'t prepare sql \'%s\'',
                $sql
            ));
        }

        if (! $stmt->execute($params)) {
            throw new \RuntimeException(sprintf(
                'Can\'t execute query \'%s\'',
                $sql
            ));
        }

        return $stmt;
    }

    /**
     * Hydrate collection of raw data from DB to application models.
     *
     * @param string  $class      Hydrated model fqcn.
     * @param array[] $collection Collection of raw data from storage.
     *
     * @return object[]
     */
    protected function hydrateCollection(string $class, array $collection)
    {
        Assertion::allIsArray($collection);

        return array_map(function (array $row) use ($class) {
            return $this->hydrate($class, $row);
        }, $collection);
    }

    /**
     * Hydrate raw data from DB to application model.
     *
     * @param string $class Hydrated model fqcn.
     * @param array  $data  Raw data from storage.
     *
     * @return object
     */
    protected function hydrate(string $class, array $data)
    {
        $model = $this->doHydrate($class, $data);

        if (! $model instanceof $class) {
            throw new \RuntimeException(sprintf(
                'Expects instance of \'%s\' but got \'%s\'',
                $class,
                is_object($model) ? get_class($model) : gettype($model)
            ));
        }

        return $model;
    }

    /**
     * @param string $class Hydrated model fqcn.
     * @param array  $data  Raw data from storage.
     *
     * @return object Instance of specified model.
     */
    private function doHydrate(string $class, array $data)
    {
        $reflection = new \ReflectionClass($class);

        $properties = $reflection->getProperties();
        $model = $reflection->newInstanceWithoutConstructor();

        try {
            foreach ($properties as $property) {
                $property->setAccessible(true);
                //
                // Convert property name to underscore.
                //
                $columnName = strtolower(preg_replace('/\B([A-Z])/', '_$1', $property->getName()));

                if (array_key_exists($columnName, $data)) {
                    $property->setValue($model, $data[$columnName]);
                }
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf(
                'Can\'t hydrate model \'%s\'. Got \'%s\' exception: %s',
                $class,
                get_class($exception),
                $exception->getMessage()
            ), $exception->getCode(), $exception);
        }

        return $model;
    }
}
