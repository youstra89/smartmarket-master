<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Provider;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname',    TextType::class, ['label' => 'Prénom', 'required' => true])
            ->add('lastname',     TextType::class, ['label' => 'Nom', 'required' => true])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone', 'required' => false])
            ->add('email',        TextType::class, ['label' => 'Email', 'required' => false])
            ->add('city',         TextType::class, ['label' => 'Ville', 'required' => false])
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
            ->add('observation',      TextareaType::class, ['label' => 'Observations', 'required' => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            $customer = $event->getData();
            $form = $event->getForm();

            if (!$customer || null === $customer->getId()) {
                $form
                    ->add('reference',   TextType::class, ['label' => 'Référence', 'required' => true])
                    // ->add('acompte',   NumberType::class, ['label' => 'Acomptes ou avances versés', 'required' => true])
                    // ->add('arriere_initial',   NumberType::class, ['label' => 'Arriérés à l\'enregistrement', 'required' => true])

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
