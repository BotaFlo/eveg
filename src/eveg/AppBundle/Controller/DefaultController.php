<?php
// src/eveg/AppBundle/Controller/DefaultController.php

namespace eveg\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use eveg\AppBundle\Entity\SyntaxonCore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
	public function indexAction()
	{
		return $this->render('evegAppBundle:Default:homepage.html.twig');
	}

	public function howtoAction()
	{
		return $this->render('evegAppBundle:Default:howto.html.twig');
	}

	public function contactAction()
	{
		
		return $this->render('evegAppBundle:Default:contact.html.twig');
	}


	public function showOneAction($id)
	{
		
		$em = $this->getDoctrine()->getManager();

		// Get repartition filters
		$repFilters = $this->get('eveg_app.repFilters');
    	$depFrFilter = $repFilters->getDepFrFilterSession($json = false);
    	$ueFilter = $repFilters->getUeFilterSession($json = false);

		// Retrieve syntaxon according to user's rights
		$findGoodRepo = $this->get('eveg_app.get_syntaxon_according_user');
		$syntaxon = $findGoodRepo->getSyntaxon($id, $depFrFilter, $ueFilter);

		// Do we found a syntaxon ? (possible null because of the repartition filters or the $id doesn't exists)
		if($syntaxon == null){
			// Not found exception
			Throw new HttpException(404, "Sorry, we can't find the syntaxon you're looking for ! Please check your repartition filters. If this problem persists, contact us.");
		}

		// is a valid syntaxon ?
		if(ereg("syn", $syntaxon->getLevel())) {
			// Checks pro parte syntaxon
        	$entities = $em->getRepository('evegAppBundle:SyntaxonCore')->findBySyntaxon($syntaxon->getSyntaxonName(), $syntaxon->getSyntaxonAuthor());

        	// is a pro parte syntaxon ?
        	//print_r($syntaxon->getSyntaxon().'<br />');
        	//print_r(count($entities).'<br />');
        	//print_r($entities);
        	if(count($entities) > 1) {
        		print('syn >1');
        		$catCodes = array();
        		foreach ($entities as $key => $entity) {
        			array_push($catCodes, $entity->getCatminatCode());
        		}
        		$multipleSyntaxon = $em->getRepository('evegAppBundle:SyntaxonCore')->findValidSyntaxonByCatminatCode($catCodes);
        		return $this->render('evegAppBundle:Default:showOne.html.twig', array(
					'multipleSyntaxon' => $multipleSyntaxon,
					'redirectionMultipleSyntaxon' => $syntaxon
				));
        	} else {
        		// REDIRECTION
        		$successfullSyntaxon = $em->getRepository('evegAppBundle:SyntaxonCore')->findValidSyntaxonByCatminatCode($entities[0]->getCatminatCode());
        		// flashbag
        		$this->get('session')->getFlashBag()->add(
		            'info',
		            'Vous avez été redirigé vers le syntaxon retenu pour votre recherche concernant "'.$syntaxon->getSyntaxon().'"'
		        );

        		return $this->redirect($this->generateUrl('eveg_show_one', 
        			array(
        				'id' => $successfullSyntaxon[0]->getId()
        				)
        		));
        		

        		/*$redirectionSyntaxon = $entities[0];
        		return $this->render('evegAppBundle:Default:showOne.html.twig', array(
					'syntaxon' => $successfullSyntaxon[0],
					'redirectionSyntaxon' => $redirectionSyntaxon
				));*/
        	}

		} else {

			// Getting catCode service
			$catCode = $this->get('eveg_app.catCode');

			$allParents = $catCode->getAllParents($syntaxon->getCatminatCode());

			// Retrieve synonyms according to user's rights
			$findGoodRepoSynonyms = $this->get('eveg_app.get_synonyms_according_user');
			$synonyms = $findGoodRepoSynonyms->getSynonyms($syntaxon->getCatminatCode(), $depFrFilter, $ueFilter);
			//$synonyms = $em->getRepository('evegAppBundle:SyntaxonCore')->getSynonyms($syntaxon->getCatminatCode());

			// repartitionDepFr to Json
			$serializer = $this->container->get('jms_serializer');
			$repDepFrJson = $serializer->serialize($syntaxon->getRepartitionDepFr(), 'json');
			$repUeJson = $serializer->serialize($syntaxon->getRepartitionEurope(), 'json');

			// baseflor
			$species = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
        					->findByCatminatCode($syntaxon->getCatminatCode());
        	$ecologicalValuesAvg = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
        					->findEcologicalAverageByCatminatCode($syntaxon->getCatminatCode());

			return $this->render('evegAppBundle:Default:showOne.html.twig', array(
			'syntaxon' => $syntaxon,
			'synonyms' => $synonyms,
			'allParents' => $allParents,
			'repDepFrJson' => $repDepFrJson,
			'repUeJson' => $repUeJson,
			'species' => $species,
			'ecologicalValuesAvg' => $ecologicalValuesAvg
		));
		}

		
	}

	public function setLanguageAction(Request $request, $lang) {

		// Get the previous route name
		//$request = $this->container->get('request');
		//$routeName = $request->get('_route');

		//$routeName = $request->headers->get('referer');

		$routeName = 'eveg_app_homepage';

		return $this->redirect($this->generateUrl($routeName, array('locale' => $lang)));
		
	}

	public function panelOptionsAction($floatLeft = false, $showFilters = true, $showFeedback = true, $showCompare = false, $showPdfExport = false)
	{
		// Repartition filters
        $repFilters = $this->get('eveg_app.repFilters');
        $depFrFilterJson = $repFilters->getDepFrFilterSession($json = true);
        $ueFilterJson = $repFilters->getUeFilterSession($json = true);

        return $this->render('evegAppBundle:Default:Fragments/panelOptions.html.twig', array(
        	'floatLeft' => $floatLeft,
        	'repDepFrJson' => $depFrFilterJson,
			'repUeJson' => $ueFilterJson,
			'showFilters' => $showFilters,
			'showFeedback' => $showFeedback,
			'showCompare' => $showCompare,
			'showPdfExport' => $showPdfExport
		));

		//return $fragment;
	}
	
}
