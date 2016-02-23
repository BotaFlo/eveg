<?php
// src/eveg/AppBundle/CatCode/evegCatCode.php

namespace eveg\AppBundle\Utils\CatCode;

//use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use eveg\AppBundle\Utils\CatCode\Exception\evegCatCodeException;
use eveg\AppBundle\Entity\SyntaxonCore;
//use eveg\AppBundle\Entity\SyntaxonCoreRepository;

class evegCatCodeRepo
{

	private $scRepo;

	public function __construct($SyntaxonCoreRepository)
	{
		$this->scRepo = $SyntaxonCoreRepository;
	}


	public function getDirectChild($catminatCode, $returnArray = false, $ueFilter = null)
	{

		$child = $scRepo->getDirectChild($catminatCode, $returnArray, $ueFilter);

		return $child[0];

	}

	public function getChildren($catminatCode, $returnArray = false, $ueFilter = null)
	{

		$children = $scRepo->getChildren($catminatCode, $returnArray, $ueFilter);

		return $children;
		
	}

	public function catminatCodeToObject($catminatCode)
	{
		/*
		*
		* Converts one or more catminatCode(s) to objects
		* Allows $catminatCode to be an array (multiple codes) or a string (single code)
		*
		*/

		if(is_string($catminatCode)){

			$uniqueObject = $scRepo->findValidOneByCatminatCode($catminatCode);

			return $uniqueObject;

		} elseif(is_array($catminatCode)) {

			$multipleObjects = $scRepo->findValidSyntaxonByCatminatCode($catminatCode);

			return $multipleObjects;

		}


	}

}