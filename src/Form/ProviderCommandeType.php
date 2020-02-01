<?php

namespace App\Form;

use App\Entity\Provider;
use App\Entity\ProviderCommande;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProviderCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
          ->add('additional_fees', IntegerType::class, ['label' => 'Frais supplémentaires', 'required' => true])
          ->add('transport',       IntegerType::class, ['label' => 'Transport', 'required' => true])
          ->add('currency_cost',   IntegerType::class, ['label' => 'Coût de conversion de devise', 'required' => true])
          ->add('forwarding_cost', IntegerType::class, ['label' => 'Transitaire', 'required' => true])
          ->add('dedouanement',    IntegerType::class, ['label' => 'Frais de dédouanement', 'required' => true])
          ->add('provider', EntityType::class, [
              'required' => true,
              'placeholder' => 'Sélectionner un forunisseur',
              'label' => 'Fournisseur',
              'choice_label' => function ($provider) {
                    return $provider->getFirstname() . ' ' . $provider->getLastname() . ' --- ' . $provider->getCountry()->getName();
              },
              'class' => Provider::class,
              'query_builder' => function (EntityRepository $er) {
                  return $er->createQueryBuilder('p');
              },
              'multiple' => false
          ])
      ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProviderCommande::class,
        ]);
    }
}
