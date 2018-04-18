<?php

namespace App\Repository;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface FileRepository
 *
 * @package App\Repository
 */
class FileRepository extends EntityRepository implements FileRepositoryInterface
{
    /**
     * Find file by slug.
     *
     * @param string $id File id.
     *
     * @return AbstractFile|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findById(string $id)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->find($id);
    }

    /**
     * Find file by slug.
     *
     * @param string $slug File slug.
     *
     * @return AbstractFile|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findBySlug(string $slug)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->findOneBy([ 'slug' => $slug ]);
    }

    /**
     * @return string[]
     * @psalm-return Array<int, string>
     */
    public function getTopLevelDirNames(): array
    {
        /** @psalm-var Array<int, Array{ id: string, name: string }> $results */
        $results = $this->createQueryBuilder('File')
            ->select('File.id, File.name')
            ->where('File.parent IS NULL AND File INSTANCE OF '. Directory::class)
            ->getQuery()
            ->getArrayResult();

        $names = [];

        foreach ($results as $result) {
            $names[(int) $result['id']] = $result['name'];
        }

        return $names;
    }

    /**
     * @param string $publicPath A public path to file.
     *
     * @return AbstractFile|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findByPublicPath(string $publicPath)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->findOneBy([ 'publicPath' => $publicPath ]);
    }
}
