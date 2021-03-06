<?php
// src/eveg/AppBundle/Controller/DefaultController.php

namespace eveg\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use eveg\AppBundle\Entity\SyntaxonCore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use eveg\AppBundle\Entity\Feedback;
use eveg\AppBundle\Form\Type\FeedbackSyntaxonType;

class DefaultController extends Controller
{
	public function indexAction()
	{
		$session = new Session();
		$session->set('idReferer', null);

		$wanted = $this->get('evep_app.wanted');
		$pdfsAlone = $wanted->getList($limit = 5);
		$nbPdfsAlone = $wanted->howMany();

		$whatsUp = $this->get('eveg_app.whatsup');
		$newDocuments = $whatsUp->tellMeWhatsNew($limitResults = 6);

		$nbEvegItems = $this->get('eveg_app.nbItems');
		$nbCards = $nbEvegItems->getNbCards();
		$nbDocs = $nbEvegItems->getNbPublicDocuments();

		return $this->render('evegAppBundle:Default:homepage.html.twig', array(
			'wanted' => $pdfsAlone,
			'nbTotalWanted' => $nbPdfsAlone,
			'newDocuments' => $newDocuments,
			'nbCards' => $nbCards,
			'nbDocs' => $nbDocs
		));
	}

	/**
 	 * @Security("has_role('ROLE_USER')")
 	 */
	public function activityAction()
	{

		$wanted = $this->get('evep_app.wanted');
		$pdfsAlone = $wanted->getList($limit = 5);
		$nbPdfsAlone = $wanted->howMany();

		$whatsUp = $this->get('eveg_app.whatsup');
		$newDocuments = $whatsUp->tellMeWhatsNew($limitResults = 50);

		return $this->render('evegAppBundle:Default:activity.html.twig', array(
			'wanted' => $pdfsAlone,
			'nbTotalWanted' => $nbPdfsAlone,
			'newDocuments' => $newDocuments
		));
	}

	public function participateAction()
	{

		$wanted = $this->get('evep_app.wanted');
		$pdfsAlone = $wanted->getList($limit = null);
		$nbPdfsAlone = $wanted->howMany();

		return $this->render('evegAppBundle:Default:participate.html.twig', array(
			'wanted' => $pdfsAlone,
			'nbTotalWanted' => $nbPdfsAlone
		));
	}

	public function howtoAction()
	{
		return $this->render('evegAppBundle:Default:howto.html.twig');
	}

	public function contactAction()
	{
		
		return $this->render('evegAppBundle:Default:contact.html.twig');
	}

	public function legalNoticeAction()
	{
		return $this->render('evegAppBundle:Default:legalNotice.html.twig');
	}

	public function downloadPdfAction($id)
	{
		
		$pageUrl = $this->generateUrl('eveg_show_simple_view', array('id' => $id), true); // use absolute path!

		return new Response(
		    $this->get('knp_snappy.pdf')->getOutput($pageUrl),
		    200,
		    array(
		        'Content-Type'          => 'application/pdf',
		        'Content-Disposition'   => 'attachment; filename='.$id.'.pdf'
		    )
		);

	}

	public function simpleViewAction($id)
	{
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

		// Getting catCode service
		$catCode = $this->get('eveg_app.catCode');
		$allParents = $catCode->getAllParents($syntaxon->getCatminatCode());

		// Retrieve synonyms according to user's rights
		$findGoodRepoSynonyms = $this->get('eveg_app.get_synonyms_according_user');
		$synonyms = $findGoodRepoSynonyms->getSynonyms($syntaxon->getCatminatCode(), $depFrFilter, $ueFilter);

		// repartitionDepFr to Json
		$serializer = $this->container->get('jms_serializer');
		$repDepFrJson = $serializer->serialize($syntaxon->getRepartitionDepFr(), 'json');
		$repUeJson = $serializer->serialize($syntaxon->getRepartitionEurope(), 'json');

		// baseflor
		$species = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
    					->findByCatminatCode($syntaxon->getCatminatCode());
    	$ecologicalValuesAvg = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
    					->findEcologicalAverageByCatminatCode($syntaxon->getCatminatCode());

    	return $this->render('evegAppBundle:Default:exportPdf.html.twig', array(
			'id' => $id,
			'syntaxon' => $syntaxon,
			'synonyms' => $synonyms,
			'allParents' => $allParents,
			'repDepFrJson' => $repDepFrJson,
			'repUeJson' => $repUeJson,
			'species' => $species,
			'ecologicalValuesAvg' => $ecologicalValuesAvg
		));

	}

	public function startAction()
	{

		$em = $this->getDoctrine()->getManager();

		// Get repartition filters
		$repFilters = $this->get('eveg_app.repFilters');
    	$depFrFilter = $repFilters->getDepFrFilterSession($json = false);
    	$ueFilter = $repFilters->getUeFilterSession($json = false);

    	$habClassLevelSyntaxons = $em->getRepository('evegAppBundle:SyntaxonCore')->findHabClassLevels($depFrFilter, $ueFilter, $limitItems = null);

    	$popularSyntaxons = $em->getRepository('evegAppBundle:SyntaxonCore')->getPopularSyntaxons($depFrFilter, $ueFilter, $limitItems = 8);

		return $this->render('evegAppBundle:Default:start.html.twig', array(
			'habClassLevelSyntaxons' => $habClassLevelSyntaxons,
			'popularSyntaxons' => $popularSyntaxons
		));

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
        	if(count($entities) > 1) {
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
        	}

		} else {

			// Getting catCode service
			$catCode = $this->get('eveg_app.catCode');

			$allParents = $catCode->getAllParents($syntaxon->getCatminatCode());

			// Retrieve synonyms according to user's rights
			$findGoodRepoSynonyms = $this->get('eveg_app.get_synonyms_according_user');
			$synonyms = $findGoodRepoSynonyms->getSynonyms($syntaxon->getCatminatCode(), $depFrFilter, $ueFilter);

			// repartitionDepFr to Json
			$serializer = $this->container->get('jms_serializer');
			$repDepFrJson = $serializer->serialize($syntaxon->getRepartitionDepFr(), 'json');
			$repUeJson = $serializer->serialize($syntaxon->getRepartitionEurope(), 'json');

			// baseflor
			$species = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
        					->findByCatminatCode($syntaxon->getCatminatCode());
        	$ecologicalValuesAvg = $this->getDoctrine()->getRepository('evegAppBundle:Baseflor')
        					->findEcologicalAverageByCatminatCode($syntaxon->getCatminatCode());

        	// Set idReferer inside session (makes it easier to find the $id when applying a repartition filter)
			$session = new Session();
			$session->set('idReferer', $syntaxon->getId());
			$session->set('syntaxonNameReferer', $syntaxon->getSyntaxonName());

			// Syntaxon hit + 1
			$syntaxon->setHit($syntaxon->getHit() + 1);
			$em->flush();

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

	public function panelOptionsAction($syntaxonId = null, $syntaxonName = null, $syntaxonLevel = null, $about, $floatLeft = false, $showFilters = true, $showFeedback = true, $showCompare = false, $showPdfExport = false)
	{
		// Repartition filters
        $repFilters = $this->get('eveg_app.repFilters');
        $depFrFilterJson = $repFilters->getDepFrFilterSession($json = true);
        $ueFilterJson = $repFilters->getUeFilterSession($json = true);

        // Feedback
    	$formFeedbackSyntaxon = $this->forward('evegAppBundle:Feedback:feedback', array(
    		'syntaxonName' => $syntaxonName,
    		'about'		   => $about
    	))->getContent();

        return $this->render('evegAppBundle:Default:Fragments/panelOptions.html.twig', array(
        	'syntaxonId' => $syntaxonId,
        	'syntaxonLevel' => $syntaxonLevel,
        	'formFeedbackSyntaxon' => $formFeedbackSyntaxon,
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

	public function panelParticipateAction()
	{
		$em = $this->getDoctrine()->getManager();
		$count = $em->getRepository('evegAppBundle:SyntaxonCore')->getTotalCount();

		return $this->render('evegAppBundle:Default:Fragments/participateThumbnail.html.twig', array(
			'' => $count
		));
	}

	public function rainbowAction()
	{
		$session = new Session();
		$r = $session->get('rainbow');

		if(!$r or $r == 0 )
		{
			$session->set('rainbow', 1);
		} else {
			$session->set('rainbow', 0);
		}
			
		return new Response($session->get('rainbow'));
	}
	
}
