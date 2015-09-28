<?php

// src/eveg/AppBundle/Menu/Builder.php
// https://gist.github.com/nateevans/9958390

 
namespace eveg\AppBundle\Menu;
 
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

 
class MenuBuilder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
    	$menu = $factory->createItem('root');
    	$menu->setChildrenAttribute('class', 'nav navbar-nav');
 
		$menu->addchild('Home', array('route' => 'eveg_app_homepage', 'label' => ''))
			->setAttribute('glyphicon', 'glyphicon-home');
		
		$menu->addChild('App', array('route' =>'eveg_admin_test', 'label' => 'App'));
		
		$menu->addChild('HowTo', array('route' => 'eveg_app_howto', 'label' => 'Aide'));
 
		$menu->addChild('Contact', array('route' => 'eveg_app_contact', 'label' => 'Contact'));
 
        return $menu;
    }
 
    public function userMenu(FactoryInterface $factory, array $options)
    {
    	$menu = $factory->createItem('root');
    	$menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');
 
    	/*
    	You probably want to show user specific information such as the username here. That's possible! Use any of the below methods to do this.*/
		
		$user = $this->container->get('security.context')->getToken()->getUser(); // Get username of the current logged in user_error()

		// Check if the visitor has any authenticated roles
    	if($this->container->get('security.context')->isGranted(array('ROLE_ADMIN'))) {
    		$menu->addChild('User', array('label' => $user->getUserName()))
				->setAttribute('dropdown', true)
				->setAttribute('icon', 'fa fa-user');
				$menu['User']->addChild('Account', array('route' => 'fos_user_profile_edit', 'label' => 'Mon compte'));
	    		$menu['User']->addChild('Dashboard', array('route' => 'eveg_admin_dashboard', 'label' => 'Dashboard'));
	    		$menu['User']->addChild('Logout', array('route' => 'fos_user_security_logout', 'label' => 'Déconnexion'));
    	} elseif($this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
	    	$menu->addChild('User', array('label' => $user->getUserName()))
				->setAttribute('dropdown', true)
				->setAttribute('icon', 'fa fa-user');
				$menu['User']->addChild('Account', array('route' => 'fos_user_profile_edit'));
				$menu['User']->addChild('Logout', array('route' => 'fos_user_security_logout', 'label' => 'Déconnexion'));
    	} else {
	    	$menu->addChild('Anonymous', array('route' => 'fos_user_security_login', 'label' => 'Connexion'));
 
    	}
		 
        return $menu;
    }

    public function dashboardMenu(FactoryInterface $factory, array $options)
    {
    	$menu = $factory->createItem('root');
    	$menu->setChildrenAttribute('class', 'nav navbar-nav');
 
		$menu->addchild('Home', array('route' => 'eveg_app_homepage', 'label' => ''))
			->setAttribute('glyphicon', 'glyphicon-home');
		
		$menu->addChild('Users', array('route' =>'eveg_admin_users', 'label' => 'Utilisateurs'));
		
		$menu->addChild('Baseveg', array('route' => 'admin_syntaxon', 'label' => 'Baseveg'));
 
		$menu->addChild('Translation', array('route' => 'eveg_app_contact', 'label' => 'Traduction'));

		$menu->addChild('LiveTest', array('route' => 'eveg_admin_test', 'label' => 'Test@dev'));
 
        return $menu;
    }
}