<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Provider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname',    TextType::class, ['label' => 'Prénom', 'required' => true])
            ->add('lastname',     TextType::class, ['label' => 'Nom', 'required' => true])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone', 'required' => true])
            ->add('email',        TextType::class, ['label' => 'Email', 'required' => true])
            ->add('city',         TextType::class, ['label' => 'Ville', 'required' => true])
            ->add('numero_compte_bancaire', TextType::class, ['label' => 'Numéro de compte bancaire', 'required' => false])
            ->add('nationalite',      TextType::class, ['label' => 'Nationalité', 'required' => false])
            ->add('country', EntityType::class, [
                'required' => true,
                'class'    => Country::class,
                'choice_label' => 'name',
                'label'    => 'Pays',
                'multiple' => false,
                'placeholder' => 'Sélectionner un pays'
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            $customer = $event->getData();
            $form = $event->getForm();

            if (!$customer || null === $customer->getId()) {
                $form
                    ->add('reference',   TextType::class, ['label' => 'Référence', 'required' => true])
                ;
            }
            else{
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Provider::class,
        ]);
    }
}
