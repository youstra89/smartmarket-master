<?php

namespace App\Form;

use App\Entity\ChangePWD;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ChangePWDType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', PasswordType::class, array('label' => 'Ancien mot de passe', 'required' => true))
            ->add('new_password', PasswordType::class, array('label' => 'Nouveau mot de passe', 'required' => true))
            ->add('new_password1', PasswordType::class, array('label' => 'Confirmation nouveau mot de passe', 'required' => true))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ChangePWD::class,
            'csrf_protection' => false
        ));
    }
}

?>
