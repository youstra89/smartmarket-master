<?php

namespace App\Form;

use App\Entity\Depense;
use App\Entity\TypeDepense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class DepenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextType::class, ['label' => 'Description', 'required' => true])
            ->add('amount',      TextType::class, ['label' => 'Montant', 'required' => true])
            ->add('type',    EntityType::class, [
                'required' => true,
                'class'    => TypeDepense::class,
                'choice_label' => 'label',
                'label'    => 'Type de dépense',
                'multiple' => false,
                'placeholder' => 'Sélectionner un élément'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Depense::class,
        ]);
    }
}
