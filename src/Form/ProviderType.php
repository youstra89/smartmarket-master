<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Provider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reference',    TextType::class, ['label' => 'Référence', 'required' => true])
            ->add('firstname',    TextType::class, ['label' => 'Prénom', 'required' => true])
            ->add('lastname',     TextType::class, ['label' => 'Nom', 'required' => true])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone', 'required' => true])
            ->add('email',        TextType::class, ['label' => 'Email', 'required' => true])
            ->add('city',         TextType::class, ['label' => 'Ville', 'required' => true])
            ->add('country', EntityType::class, [
                'required' => true,
                'class'    => Country::class,
                'choice_label' => 'name',
                'label'    => 'Pays',
                'multiple' => false,
                'placeholder' => 'Sélectionner un pays'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Provider::class,
        ]);
    }
}
