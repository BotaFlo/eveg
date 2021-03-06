<?php

namespace eveg\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * SyntaxonCoreRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SyntaxonCoreRepository extends EntityRepository
{

	// Returns the tree trunk (Ajax call from FancyTree)
	public function getBaseTree($depFrFilter = null, $ueFilter = null, $exclusive = false)
	{
		$qb = $this->createQueryBuilder('s');

		// specify the fields to fetch (unselected fields will have a null value)
		$qb->select('s')
			->where('s.level = :level')
			->setParameter('level', 'HAB');

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		$tree = $qb->getQuery()->getResult();

		return $tree;
	}

	// Returns children nodes of the $id node (Ajax call from FancyTree)
	public function getNextTreeNode($id, $nextLevel, $depFrFilter = null, $ueFilter = null, $exclusive = false)
	{
		
		// Grabbing syntaxon from $id
			$qb = $this->createQueryBuilder('s');

			$qb->select('s')
				->where('s.id = :id')
				->setParameter('id', $id);

			$syntaxon = $qb->getQuery()->getSingleResult();

			$catminatCode = $syntaxon->getCatminatCode();

		// Grabbing children
			$qb = $this->createQueryBuilder('s');

			$qb->select('s')
				->where('s.catminatCode LIKE :catminatCode')
				->andWhere('s.catminatCode != :catminatCodeSimple')
				->andWHere('s.level = :nextLevel')
				->setParameter('catminatCode', $catminatCode.'%')
				->setParameter('catminatCodeSimple', $catminatCode)
				->setParameter('nextLevel', $nextLevel)
				->orderBy('s.catminatCode', 'ASC');

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}
			
		return $qb->getQuery()->getResult();

	}

	// Returns the children of one catminat code
	public function getChildren($catminatCode, $returnArray = false, $ueFilter = null, $depFrFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.catminatCode LIKE :catminatCode')
			->andWhere('s.catminatCode != :catminatCodeSimple')
			->setParameter('catminatCode', $catminatCode.'%')
			->setParameter('catminatCodeSimple', $catminatCode)
			->orderBy('s.catminatCode', 'ASC');

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, false);
		}
		// Department filter
		/*if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter);
		}*/

		if($returnArray == true) {
			return $qb->getQuery()->getArrayResult();
		} else {
			return $qb->getQuery()->getResult();
		}
	}

	// Returns the first child of one catminat code
	// regardless to syntaxon level
	public function getFirstDirectChildByCatminatCode($catminatCode, $returnArray = false, $ueFilter = null, $depFrFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.catminatCode LIKE :catminatCode')
			->andWhere('s.catminatCode != :catminatCodeSimple')
			->setParameter('catminatCode', $catminatCode.'%')
			->setParameter('catminatCodeSimple', $catminatCode)
			->orderBy('s.catminatCode', 'ASC')
			->setMaxResults(1);

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, false);
		}
		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter);
		}

		if($returnArray == true) {
			return $qb->getQuery()->getArrayResult();
		} else {
			return $qb->getQuery()->getResult();
		}
		
	}

	public function getDirectChildrenByCatminatCode($catminatCode, $nextLevel, $depFrFilter = null, $ueFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.catminatCode LIKE :catminatCode')
			->andWhere('s.catminatCode != :catminatCodeSimple')
			->andWHere('s.level = :nextLevel')
			->setParameter('catminatCode', $catminatCode.'%')
			->setParameter('catminatCodeSimple', $catminatCode)
			->setParameter('nextLevel', $nextLevel)
			->orderBy('s.catminatCode', 'ASC');

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, false);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, false);
		}

		return $qb->getQuery()->getResult();

	}

	// Returns all objects from their catminatCodes (listed in $array)
	public function findAllByCatminatCodes($array)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.catminatCode = :catminatCode')
			->setParameter('catminatCode', $array);

		return $qb->getQuery()->getResult();

	}

	// Returns syntaxon full name from (array)$ids
	public function findNamesById($idsArray)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s.id, s.syntaxonName, s.syntaxonAuthor');
		foreach ($idsArray as $key => $id) {
			$qb->orWhere('s.id = '.(int)$id);
		}
			

		return $qb->getQuery()->getResult();
	}

	// Returns data for the search engine
	public function findForSearchEngine($searchedTerm, $useSynonyms = true, $depFrFilter = null, $ueFilter = null, $exclusive = false)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where(
		   	$qb->expr()->like(
		   			$qb->expr()->concat('s.syntaxonName', 's.syntaxonAuthor'),
		   			':searchedTerm')
		   	)
		   ->groupBy('s.syntaxonName')
		   ->addGroupBy('s.syntaxonAuthor')
		   ->setParameter('searchedTerm', $searchedTerm);

		// Synonym filter
		if ($useSynonyms == false || $useSynonyms == "false") {
			$qb->andWhere('s.level NOT LIKE :syn')
			   ->setParameter('syn', '%syn%');
		}

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}
		//return print($depFrFilter);
		//print($qb->getQuery()->getSql());
		return $qb->getQuery()->getResult();

	}

	/**
	 * departmentFrFilter function
	 * adds a filter in order to select only french departments within selected vegetations are presents (!= zero)
	 *
	 * @param QueryBuilder $qb
	 * @param string $depFrFilter Array data (ex: '[_59: "_59", _62: "_62"]' will add a filter for 2 departments)
	 * @param bool $exclusive TRUE : to be selected one vegetation have to be present in every departments (limit the number of results) ; FALSE : it only has to be present in one department of the filter (default)
	 */
	public function departmentFrFilter(QueryBuilder $qb, $depFrFilter, $exclusive = false)
	{
		$orModule = $qb->expr()->orx();
		$andModule = $qb->expr()->andx();
		$module = ($exclusive ? $andModule : $orModule);
		$qb->leftJoin('s.repartitionDepFr', 'repDepFr')
	   	   ->addSelect('repDepFr');

		foreach ($depFrFilter as $key => $value) {
			if($value != null) {
				//$key = "_59";
				$module->add($qb->expr()->neq('repDepFr.'.$key, ':zero'));
			}
		}
		$qb->andWhere($module)->setParameter('zero', 0);
	}

	/**
	 * ueFilter function
	 * adds a filter in order to select countries within selected vegetations are presents (!= zero or != 5)
	 *
	 * @param QueryBuilder $qb
	 * @param string $ueFilter Array data
	 * @param bool $exclusive TRUE : to be selected one vegetation have to be present in every country (limit the number of results) ; FALSE : it only has to be present in one country of the filter (default)
	 */
	public function ueFilter(QueryBuilder $qb, $ueFilter, $exclusive = false)
	{
		$orModule = $qb->expr()->orx();
		$andModule = $qb->expr()->andx();
		$module = ($exclusive ? $andModule : $orModule);
		$module2 = $andModule;
		$qb->leftJoin('s.repartitionEurope', 'repUe')
	   	   ->addSelect('repUe');

		foreach ($ueFilter as $key => $value) {
			if($value != null) {
				$module->add($qb->expr()->neq('repUe.'.$key, ':zero'));
				$module2->add($qb->expr()->neq('repUe.'.$key, ':absent'));
			}
		}
		$qb->andWhere($module)->setParameter('zero', 0);
		$qb->andWhere($module2)->setParameter('absent', 5);
	}

	public function findBySyntaxon($syntaxonName, $syntaxonAuthor)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.syntaxonName = :syntaxonName')
			->andWhere('s.syntaxonAuthor = :syntaxonAuthor')
			->setParameter('syntaxonName', $syntaxonName)
			->setParameter('syntaxonAuthor', $syntaxonAuthor);

		return $qb->getQuery()->getResult();
	}

	public function findValidSyntaxonByCatminatCode($catminatCode)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->add('where', $qb->expr()->in('s.catminatCode', ':array'))
			->setParameter('array', $catminatCode);
		$qb->andWhere($qb->expr()->notLike('s.level', $qb->expr()->literal('syn%')));
		$qb->orderBy('s.catminatCode', 'ASC');

		return $qb->getQuery()->getResult();
	}
	
	public function findValidOneByCatminatCode($catminatCode)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
			->where('s.catminatCode = :catminatCode')
			->setParameter('catminatCode', $catminatCode);
		$qb->andWhere($qb->expr()->notLike('s.level', $qb->expr()->literal('syn%')));
		$qb->orderBy('s.catminatCode', 'ASC');

		return $qb->getQuery()->getResult();
	}

	public function findSynonymsByCatminatCode($catminatCode)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.catminatCode = :catminatCode')
		   ->setParameter('catminatCode', $catminatCode)
		   ->andWhere("s.level LIKE 'syn%'")
		   ;

		return $qb->getQuery()->getResult();
	}

	public function findSynonymsByCatminatCodePublicData($catminatCode, $depFrFilter = null, $ueFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.catminatCode = :catminatCode')
		   ->setParameter('catminatCode', $catminatCode)
		   ->andWhere("s.level LIKE 'syn%'")
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public')
		   ->setParameter('public', 'public')
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;

		return $qb->getQuery()->getResult();
	}

	public function findSynonymsByCatminatCodePublicPrivateData($catminatCode, $user, $depFrFilter = null, $ueFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.catminatCode = :catminatCode')
		   ->setParameter('catminatCode', $catminatCode)
		   ->andWhere("s.level LIKE 'syn%'")
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public OR (photo.visibility = :private AND photo.user = :user)')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public OR (file.visibility = :private AND file.user = :user)')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public OR (link.visibility = :private AND link.user = :user)')
		   ->setParameter('public', 'public')
		   ->setParameter('private', 'private')
		   ->setParameter('user', $user)
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;

		return $qb->getQuery()->getResult();
	}

	public function findSynonymsByCatminatCodePublicPrivateCircleData($catminatCode, $user, $depFrFilter = null, $ueFilter = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.catminatCode = :catminatCode')
		   ->setParameter('catminatCode', $catminatCode)
		   ->andWhere("s.level LIKE 'syn%'")
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public OR photo.visibility = :circle OR (photo.visibility = :private AND photo.user = :user)')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public OR file.visibility = :circle OR (file.visibility = :private AND file.user = :user)')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public OR link.visibility = :circle OR (link.visibility = :private AND link.user = :user)')
		   ->setParameter('public', 'public')
		   ->setParameter('circle', 'group')
		   ->setParameter('private', 'private')
		   ->setParameter('user', $user)
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;

		
		return $qb->getQuery()->getResult();
	}

	public function findById($id)
	{
		$qb = $this->createQueryBuilder('s');
		$qb->select('s')
		   ->where('s.id = :id')
		   ->setParameter('id', $id)
		   ;

		  return $qb->getQuery()->getOneOrNullResult();
	}

	public function findByIdPublicData($id, $depFrFilter = null, $ueFilter = null, $exclusive = null)
	{
		$qb = $this->createQueryBuilder('s');
		$qb->select('s')
		   ->where('s.id = :id')
		   ->setParameter('id', $id)
		   ->leftJoin('s.repartitionDepFr', 'depFr')
		   ->leftJoin('s.repartitionEurope', 'europe')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public')
		   ->setParameter('public', 'public')
		   ->addSelect('depFr')
		   ->addSelect('europe')
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;
		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		  return $qb->getQuery()->getOneOrNullResult();
	}

	public function findByIdPublicPrivateCircleData($id, $user, $depFrFilter = null, $ueFilter = null, $exclusive = false)
	{
		$qb = $this->createQueryBuilder('s');
		$qb->select('s')
		   ->where('s.id = :id')
		   ->setParameter('id', $id)
		   ->leftJoin('s.repartitionDepFr', 'depFr')
		   ->leftJoin('s.repartitionEurope', 'europe')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public OR photo.visibility = :circle OR (photo.visibility = :private AND photo.user = :user)')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public OR file.visibility = :circle OR (file.visibility = :private AND file.user = :user)')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public OR link.visibility = :circle OR (link.visibility = :private AND link.user = :user)')
		   ->setParameter('public', 'public')
		   ->setParameter('circle', 'group')
		   ->setParameter('private', 'private')
		   ->setParameter('user', $user)
		   ->addSelect('depFr')
		   ->addSelect('europe')
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		  return $qb->getQuery()->getOneOrNullResult();
	}

	public function findByIdPublicPrivateData($id, $user, $depFrFilter = null, $ueFilter = null, $exclusive = false)
	{
		$qb = $this->createQueryBuilder('s');
		$qb->select('s')
		   ->where('s.id = :id')
		   ->setParameter('id', $id)
		   ->leftJoin('s.repartitionDepFr', 'depFr')
		   ->leftJoin('s.repartitionEurope', 'europe')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public OR (photo.visibility = :private AND photo.user = :user)')
		   ->leftJoin('s.syntaxonFiles', 'file', 'WITH', 'file.visibility = :public OR (file.visibility = :private AND file.user = :user)')
		   ->leftJoin('s.syntaxonHttpLinks', 'link', 'WITH', 'link.visibility = :public OR (link.visibility = :private AND link.user = :user)')
		   ->setParameter('public', 'public')
		   ->setParameter('private', 'private')
		   ->setParameter('user', $user)
		   ->addSelect('depFr')
		   ->addSelect('europe')
		   ->addSelect('photo')
		   ->addSelect('file')
		   ->addSelect('link')
		   ;

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		  return $qb->getQuery()->getOneOrNullResult();
	}

	public function getAllNotSynonyms()
	{
		$qb = $this->createQueryBuilder('s');
		return $qb->select('COUNT(s)')
				  ->where('s.level NOT LIKE :synonym')
				  ->setParameter('synonym', '%syn%')
		   		  ->getQuery()
		   		  ->getSingleScalarResult();
	}

	public function findAllForSitemap()
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s.id');

		return $qb->getQuery()->getResult();
	}

	/**
	 * findByLevel function
	 * Returns all syntaxons for a given level
	 *
	 * @param string $level
	 * @param string $depFrFilter
	 * @param string $ueFilter
	 * @param integer $limitItems
	 */
	public function findByLevel($level, $depFrFilter = null, $ueFilter = null, $limitItems = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.level = :level')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public')
   		   ->setParameter('level', $level)
   		   ->setParameter('public', 'public')
   		   ->addSelect('photo')
   		   ->orderBy('s.catminatCode', 'ASC')
		   ;

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		if($limitItems != null) {
			$qb->setMaxResults($limitItems);
		}

		return $qb->getQuery()->getResult();

	}

	/**
	 * findHabClassLevels function
	 * Returns all syntaxons for HAB & CLA levels
	 *
	 * @param string $depFrFilter
	 * @param string $ueFilter
	 * @param integer $limitItems
	 */
	public function findHabClassLevels($depFrFilter = null, $ueFilter = null, $limitItems = null)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->where('s.level = :hab')
		   ->orWhere('s.level = :cla')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public')
   		   ->setParameter('hab', 'HAB')
   		   ->setParameter('cla', 'CLA')
   		   ->setParameter('public', 'public')
   		   ->addSelect('photo')
   		   ->orderBy('s.catminatCode', 'ASC')
		   ;

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		if($limitItems != null) {
			$qb->setMaxResults($limitItems);
		}

		return $qb->getQuery()->getResult();

	}

	/**
	 * getPopularSyntaxons function
	 * Returns the most popular syntaxons
	 *
	 * @param string $level
	 * @param string $depFrFilter
	 * @param string $ueFilter
	 * @param integer $limitItems
	 */
	public function getPopularSyntaxons($depFrFilter = null, $ueFilter = null, $limitItems = 10)
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		   ->leftJoin('s.syntaxonPhotos', 'photo', 'WITH', 'photo.visibility = :public')
   		   ->setParameter('public', 'public')
   		   ->addSelect('photo')
   		   ->orderBy('s.hit', 'DESC')
		   ;

		// Department filter
		if($depFrFilter != null) {
			$this->departmentFrFilter($qb, $depFrFilter, $exclusive);
		}

		// UE filter
		if($ueFilter != null) {
			$this->ueFilter($qb, $ueFilter, $exclusive);
		}

		if($limitItems != null) {
			$qb->setMaxResults($limitItems);
		}

		return $qb->getQuery()->getResult();
	}

	public function findAllWhithoutRelation()
	{
		$qb = $this->createQueryBuilder('s');

		$qb->select('s')
		;
		$qb->setMaxResults(500);

		return $qb->getQuery()->getResult();
	}
}
