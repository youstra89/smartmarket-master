<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\CustomerType as Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reference',    TextType::class, ['label' => 'Référence', 'required' => true])
            ->add('firstname',    TextType::class, ['label' => 'Prénom', 'required' => true])
            ->add('lastname',     TextType::class, ['label' => 'Nom', 'required' => true])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone', 'required' => true])
            ->add('email',        EmailType::class, ['label' => 'Email', 'required' => false])
            ->add('address',      TextType::class, ['label' => 'Adresse', 'required' => true])
            ->add('residence',    TextType::class, ['label' => 'Résidence', 'required' => true])
            ->add('type', EntityType::class, [
                'required' => true,
                'class'    => Type::class,
                'choice_label' => 'type',
                'label'    => 'Type de client',
                'multiple' => false,
                'placeholder' => 'Sélectionner un type de client'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
