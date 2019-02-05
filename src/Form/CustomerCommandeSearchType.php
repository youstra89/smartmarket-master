<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\CustomerCommandeSearch;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CustomerCommandeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer', EntityType::class, [
              'required' => false,
              'label' => false,
              'placeholder' => 'Client',
              'choice_label' => function ($customer) {
                    return $customer->getFirstname() . ' ' . $customer->getLastname() . ' --- ' . $customer->getPhoneNumber();
              },
              'class' => 'App\Entity\Customer',
              'query_builder' => function (EntityRepository $er) {
                  return $er->createQueryBuilder('c');
              },
              'multiple' => false
            ])
            ->add('products', EntityType::class, [
              'required' => false,
              'label' => false,
              'placeholder' => 'Marchandises',
              'choice_label' => function ($product) {
                    return $product->getCategory()->getName() . ' ' . $product->getMark()->getLabel() . ' ' . $product->getDescription();
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
            'data_class' => CustomerCommandeSearch::class,
            'method' => 'get',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
      return '';
    }
}
