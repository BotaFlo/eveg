<?php
// src/eveg/AppBundle/Controller/SyntaxonFileController.php

namespace eveg\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use eveg\AppBundle\Entity\SyntaxonCore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use eveg\AppBundle\Form\Type\SyntaxonCoreFileType;
use eveg\AppBundle\Entity\SyntaxonFile;
use eveg\AppBundle\Form\Type\SyntaxonFileType;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * SyntaxonFile controller.
 *
 */
class SyntaxonFileController extends Controller
{
	/**
	* @ParamConverter("syntaxon", class="evegAppBundle:syntaxonCore",
	* 	options= { "repository_method" = "findByIdWithAllEntities" })
	* 
	*/
	public function addFileAction(SyntaxonCore $syntaxon, $id, Request $request)
	{
		
		$request = $this->getRequest();

		// Creates the form...
    	$form = $this->createForm(new SyntaxonCoreFileType());

        // ... and then hydrates it
        $form->handleRequest($request);

        // Job routine
		if($form->isValid()) {

			$data = $form->getData();

			$files = $data->getSyntaxonFiles();
			$nbFiles = count($files);

			$currentUser = $this->getUser();

			foreach ($files as $key => $file) {
				$file->setSyntaxonCore($syntaxon);
				$file->setUser($currentUser);
				$file->setOriginalName($file->getFileFile()->getClientOriginalName());
				switch ($file->getFileFile()->guessExtension()) {
					case 'csv':
					case 'ods':
					case 'xls':
					case 'xlsx':
						$file->setType('spreadsheet');
						break;
					case 'pdf':
						$file->setType('pdf');
						break;
				}

				$syntaxon->addSyntaxonFile($file);
			}

			$em = $this->getDoctrine()->getManager();
      		$em->persist($syntaxon);
      		$em->flush();

      		if ($nbFiles == 1) {
      			$request->getSession()->getFlashBag()->add('success', 'Un nouveau fichier enregistré.');
      		} elseif ($nbFiles > 1) {
      			$request->getSession()->getFlashBag()->add('success', $nbFiles.' nouveaux fichiers enregistrés.');
      		} else {
      			$request->getSession()->getFlashBag()->add('warning', 'Aucun nouveau fichier enregistré.');
      		}

      		return $this->redirect($this->generateUrl('eveg_show_one', 
        			array('id' => $id)
        		));
      		
		}

		return $this->render('evegAppBundle:Default:addFile.html.twig', array(
			'syntaxon' => $syntaxon,
			'form' => $form->createView()
		));
	}

	/**
	* @ParamConverter("syntaxon", class="evegAppBundle:syntaxonCore",
	* 	options= { "repository_method" = "findByIdWithAllEntities" })
	* @ParamConverter("file", class="evegAppBundle:SyntaxonFile", options={"id" = "idFile"})
	* 
	*/
	public function editFileAction(SyntaxonCore $syntaxon, $id, SyntaxonFile $file)
	{
		
		// author of the file == current user ?
		if($file->getUser()->getId() !== $this->getUser()->getId()) {
			Throw new HttpException(401, 'You are not allowed to edit this file.');
		}

		$request = $this->getRequest();

		// Creates the form...
    	$form = $this->createForm(new SyntaxonFileType($this->get('security.authorization_checker')), $file);

        // ... and then hydrates it
        $form->handleRequest($request);

        // Job routine
		if($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
      		$em->persist($file);
      		$em->flush();

      		$request->getSession()->getFlashBag()->add('success', 'Les informations relative à votre fichier ont été modifiées.');

      		return $this->redirect($this->generateUrl('eveg_show_one', 
        			array('id' => $id)
        		));
		}

		return $this->render('evegAppBundle:Default:editFile.html.twig', array(
			'syntaxon' => $syntaxon,
			'form' => $form->createView()
		));
	}

	/**
	* @ParamConverter("syntaxon", class="evegAppBundle:syntaxonCore",
	* 	options= { "repository_method" = "findByIdWithAllEntities" })
	* @ParamConverter("file", class="evegAppBundle:SyntaxonFile", options={"id" = "idFile"})
	* 
	*/
	public function downloadFileAction(SyntaxonCore $syntaxon, $id, SyntaxonFile $file)
	{
		// Grabing download helper
		$downloadHandler = $this->get('vich_uploader.download_handler');

		// Hit+1
		$file->setHit($file->getHit()+1);
		$em = $this->getDoctrine()->getManager();
  		$em->persist($file);
  		$em->flush();

		// Return
		$newFileName = $file->getOriginalName();
		return $downloadHandler->downloadObject($file, $fileField = 'fileFile', $className = null, $newFileName);
	}
}