<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, ['required' => true, 'label' => 'Votre mail'])
            ->add('nom', TextType::class, ['required' => true, 'label' => 'Nom'])
            ->add('username', TextType::class, ['required' => true])
            ->add('pnom', TextType::class, ['required' => true, 'label' => 'Prénom'])
            ->add('sexe', ChoiceType::class, [
              'choices' => $this->getChoices()
            ])
            ->add('residence', TextType::class, ['required' => true, 'label' => 'Commune/Ville'])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone'])
            ->add('date_naissance', BirthdayType::class, ['required' => true])
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Confirmation du mot de passe'),
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
            'csrf_protection' => false
        ));
    }

    public function getChoices()
    {
      $choices = User::SEXE;
      $output = [];
      foreach($choices as $k => $v){
        $output[$v] = $k;
      }
      return $output;
    }
}

?>
