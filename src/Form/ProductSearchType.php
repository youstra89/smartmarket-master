<?php

namespace App\Form;

use App\Entity\Mark;
use App\Entity\Category;
use App\Entity\ProductSearch;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProductSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('description', TextType::class, [
          'required' => false,
          'label' => false,
          'attr' => [
            'placeholder' => 'Entrez une description'
          ]
        ])
        ->add('mark', EntityType::class, [
          'required' => false,
          'class'    => Mark::class,
          'choice_label' => 'label',
          'label'    => false,
          'required' => false,
          'multiple' => false,
          'placeholder' => 'Sélectionnez une marque'
        ])
        ->add('category', EntityType::class, [
          'required' => false,
          'class'    => Category::class,
          'choice_label' => 'name',
          'label'    => false,
          'required' => false,
          'multiple' => false,
          'placeholder' => 'Sélectionnez une catégorie'
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductSearch::class,
            'method' => 'post',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
      return '';
    }
}
