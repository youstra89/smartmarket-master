<?php

namespace App\Form;

use App\Entity\Mark;
use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reference',   TextType::class, ['label' => 'Référence', 'required' => true])
            ->add('description', TextType::class, ['label' => 'Description', 'required' => true])
            ->add('unit_price', TextType::class, ['label' => 'Prix unitaire', 'required' => true])
            ->add('category',    EntityType::class, [
                'required' => true,
                'class'    => Category::class,
                'choice_label' => 'name',
                'label'    => 'Catégorie',
                'multiple' => false,
                'placeholder' => 'Sélectionner un élément'
            ])
            ->add('mark',        EntityType::class, [
                'required' => true,
                'class'    => Mark::class,
                'choice_label' => 'label',
                'label'    => 'Marque',
                'multiple' => false,
                'placeholder' => 'Sélectionner un élément'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
