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
            ->add('email',     EmailType      ::class, ['required' => true, 'label' => 'Email'])
            ->add('username',  TextType       ::class, ['required' => true])
            ->add('firstname', TextType       ::class, ['required' => true, 'label' => 'Prénom'])
            ->add('lastname',  TextType       ::class, ['required' => true, 'label' => 'Nom'])
            ->add('password',  RepeatedType   ::class, array(
                'type'                        =>PasswordType       :: class,
                'first_options'               =>array('label'      => 'Mot de passe'),
                'second_options'              =>array('label'      => 'Confirmation du mot de passe'),
            ))
            ->add('gender',     ChoiceType    ::class, [
              'choices'                       =>$this->getChoices()
            ])
            ->add('residence',    TextType    ::class, ['required' => true, 'label' => 'Commune/Ville'])
            ->add('birthday',     BirthdayType::class, ['required' => true])
            ->add('phone_number', TextType    ::class, ['label'    => 'Numéro de téléphone'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
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
