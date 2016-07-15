<?php
// eveg/AppBundle/Form/Type/FeedbackSyntaxonType.php

namespace eveg\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class FeedbackSyntaxonType extends AbstractType
{
    
	public function buildForm(FormBuilderInterface $builder, array $options)
	{

		$builder->add('about', 'hidden');
		$builder->add('email', EmailType::class);
		$builder->add('type', ChoiceType::class, array(
			'choices' => array(
				'interface' => 'eveg.app.show_one.feedback.syntaxon.type.interface',
				'data' => 'eveg.app.show_one.feedback.syntaxon.type.data',
				'other' => 'eveg.app.show_one.feedback.syntaxon.type.other'
			),
		));
		$builder->add('syntaxon', 'hidden');
		$builder->add('message');
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'eveg\AppBundle\Entity\Feedback',
            'cascade_validation' => true,
            'choice_translation_domain' => true,
        ));
    }

    public function getName()
    {
        return 'feedbackSyntaxon';
    }

}