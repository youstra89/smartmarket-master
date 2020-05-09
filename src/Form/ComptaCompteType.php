<?php

namespace App\Form;

use App\Entity\ComptaClasse;
use App\Entity\ComptaCompte;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ComptaCompteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numero', NumberType::class, ['label' => 'Numéro', 'required' => true])
            ->add('label', TextType::class, ['label' => 'Libellé', 'required' => true])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            $compte = $event->getData();
            $form = $event->getForm();

            if (!$compte || null === $compte->getId()) {
                $form->add('classe',    EntityType::class, [
                    'required' => true,
                    'class'    => ComptaClasse::class,
                    'choice_label' => 'label',
                    'label'    => 'Classe',
                    'multiple' => false,
                    'placeholder' => 'Sélectionner une classe'
                ]);
            }
            else{
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ComptaCompte::class,
        ]);
    }
}
