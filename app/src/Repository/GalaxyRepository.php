<?php

namespace App\Repository;

use App\Entity\Galaxy;
use App\Entity\DirectusFiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Galaxy>
 */
class GalaxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Galaxy::class);
    }

    //    /**
    //     * @return Galaxy[] Returns an array of Galaxy objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Galaxy
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAllWithFiles(): array{

        $queryResults = $this->createQueryBuilder('galaxy')
            ->select('galaxy.id, galaxy.title, galaxy.description, galaxy.modele')
            ->addSelect('file.id as file_id, file.filename_disk')
            ->innerJoin('App\Entity\Modeles', 'modele', 'WITH', 'modele.id = galaxy.modele')
            ->innerJoin('App\Entity\ModelesFiles', 'modelesFiles', 'WITH', 'modelesFiles.modeles_id = modele.id')
            ->innerJoin('App\Entity\DirectusFiles', 'file', 'WITH', 'file.id = modelesFiles.directus_files_id')
            ->orderBy('galaxy.id', 'ASC')
            ->addOrderBy('file.id', 'ASC')
            ->getQuery()
            ->getResult();

        $carouselItems = [];
        foreach ($queryResults as $resultRow) {
            $galaxyId = $resultRow['id'];
            
            if (!isset($carouselItems[$galaxyId])) {
                $carouselItems[$galaxyId] = [
                    'title' => $resultRow['title'],
                    'description' => $resultRow['description'],
                    'files' => []
                ];
            }
            
            $carouselItems[$galaxyId]['files'][] = [
                'filename_disk' => $resultRow['filename_disk']
            ];
        }
        
        return array_values($carouselItems);
    }
}
