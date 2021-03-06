<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use AppBundle\Entity\Tag;

class FilterType extends AbstractType
{
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $em) {
         $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query', TextType::class, array('required' => true))
            ->add('tag', null, array('required' => true, 'placeholder' => "Tag à appliquer"));
        ;

        $builder->get('tag')->resetViewTransformers();
        $builder->get('tag')
            ->addViewTransformer(new CallbackTransformer(
                function ($choice) use ($builder) {
                    $choiceList = $builder->get('tag')->getOption('choice_loader');
                    return (string) current($choiceList->loadValuesForChoices(array($choice)));
                },
                function ($value) use ($builder) {
                    $choiceList = $builder->get('tag')->getOption('choice_loader');
                    if ((null === $value || '' === $value) || !is_string($value)) {
                        throw new TransformationFailedException('Expected a string not null.');
                    }

                    $choices = $choiceList->loadChoicesForValues(array($value));

                    if (1 !== count($choices)) {
                        $tag = new Tag();
                        $tag->setName($value);
                        $this->em->persist($tag);

                        return $tag;
                    }

                    return current($choices);
                }
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Filter'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'filter';
    }
}
