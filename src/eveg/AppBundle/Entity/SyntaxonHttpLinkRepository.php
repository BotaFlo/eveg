<?php

namespace eveg\AppBundle\Entity;

/**
 * SyntaxonHttpLinkRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SyntaxonHttpLinkRepository extends \Doctrine\ORM\EntityRepository
{
	public function getPublicHttpLinksOrderByDatetime($limitResults = null)
	{
		$qb = $this->createQueryBuilder('h');

		$qb->select('h')
			->andWhere('h.visibility = :public')
			->orderBy('h.uploadedAt', 'DESC')
			->leftJoin('h.user', 'user')
			->leftJoin('h.syntaxonCore', 'score')
			->setParameter('public', 'public')
			->addSelect('user')
			->addSelect('score')
			;
		if($limitResults) {
			$qb->setMaxResults($limitResults);
		}

		return $qb->getQuery()->getResult();
	}

	public function getNbPublic()
	{
		$qb = $this->createQueryBuilder('h');

		$qb->select('COUNT(h)')
		   ->where('h.visibility = :public')
		   ->setParameter('public', 'public')
		   ;

		return $qb->getQuery()->getSingleScalarResult();
	}
}
