<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Provider;
use App\Entity\ProviderCommandeSearch;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProviderCommandeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('provider', EntityType::class, [
              'required' => false,
              'label' => false,
              'placeholder' => 'Fournisseur',
              'choice_label' => function ($provider) {
                    return $provider->getFirstname() . ' ' . $provider->getLastname() . ' --- ' . $provider->getCountry()->getName();
              },
              'class' => 'App\Entity\Provider',
              'query_builder' => function (EntityRepository $er) {
                  return $er->createQueryBuilder('p');
              },
              'multiple' => false
            ])
            ->add('products', EntityType::class, [
              'required' => false,
              'label' => false,
              'placeholder' => 'Marchandises',
              'choice_label' => function ($product) {
                    return $product->getLabel();
              },
              'class' => Product::class,
              'query_builder' => function (EntityRepository $er) {
                  return $er->createQueryBuilder('p');
              },
              'multiple' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProviderCommandeSearch::class,
            'method' => 'get',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
      return '';
    }
}
