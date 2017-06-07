<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SourceType extends AbstractType
{
    protected $importer;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $importer = $options['importer'];
        foreach($importer->getParameters() as $paramKey => $param) {
            $builder->add($paramKey, null, array('required' => $param['required'], "label" => $param['label'], 'attr' => array("placeholder" => $param['help'])));
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('importer' => null));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'source_parameters';
    }
}
