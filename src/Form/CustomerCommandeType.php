<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\CustomerCommande;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CustomerCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer', EntityType::class, [
                'required' => false,
                'placeholder' => 'Sélectionner un client',
                'label' => 'Client',
                'choice_label' => function ($customer) {
                      return $customer->getFirstname() . ' ' . $customer->getLastname() . ' --- ' . $customer->getPhoneNumber();
                },
                'class' => Customer::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c');
                },
                'multiple' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerCommande::class,
        ]);
    }
}
