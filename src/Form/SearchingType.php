<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;

class SearchingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {        
        $builder
            ->add('recherche', SearchType::class,[
                'required' => false,
            ])
            ->add('familles',ChoiceType::class,[
                'choices' => $options['familles'],
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                // 'multiple' => true,
                'attr' => ['class' => 'form-select form-select-lg mb-4'],
            ])
            ->add('sousFamilles',ChoiceType::class,[
                'choices' => $options['sousFamilles'],
                'placeholder' => 'Choisir une sous-catégorie',
                'required' => false,
                'multiple' => true,
                'attr' => ['class' => 'form-select form-select-lg mb-4'],
            ])
            ->add('marque',ChoiceType::class,[
                'choices' => $options['marques'],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'placeholder' => 'choisir une marque',
                'required' => false,
                'attr' => ['class' => 'form-select form-select-lg mb-4'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['familles','sousFamilles','marques']);
    }
}
