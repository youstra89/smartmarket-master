<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\CustomerType as Type;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname',    TextType::class, ['label' => 'Prénom', 'required' => true])
            ->add('lastname',     TextType::class, ['label' => 'Nom', 'required' => true])
            ->add('phone_number', TextType::class, ['label' => 'Numéro de téléphone', 'required' => false])
            ->add('email',        EmailType::class, ['label' => 'Email', 'required' => false])
            ->add('address',      TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('residence',    TextType::class, ['label' => 'Résidence', 'required' => false])
            ->add('type', EntityType::class, [
                'required' => false,
                'class'    => Type::class,
                'choice_label' => 'type',
                'label'    => 'Type de client',
                'multiple' => false,
                'placeholder' => 'Sélectionner un type de client'
            ])
            ->add('numero_compte_bancaire', TextType::class, ['label' => 'Numéro de compte bancaire', 'required' => false])
            ->add('profession',      TextType::class, ['label' => 'Profession', 'required' => false])
            ->add('nationalite',      TextType::class, ['label' => 'Nationalité', 'required' => false])
            ->add('date_naissance',   BirthdayType::class, ['label' => 'Date de naissance', 'required' => false])
            ->add('lieu_naissance',   TextType::class, ['label' => 'Lieu de naissance', 'required' => false])
            ->add('nature_piece_identite',   ChoiceType::class, [
                'label' => 'Nature de la pièce d\'identité', 
                'required' => false,
                'choices' => [
                    "CNI" => "Carte Nationale d'Identité",
                    "Passeport" => "Passeport",
                ]
            ])
            ->add('numero_piece_identite',   TextType::class, ['label' => 'Numéro de la pièce d\'identité', 'required' => false])
            ->add('date_etablissement_piece_identite',   BirthdayType::class, ['label' => 'Date d\'établissement de la pièce d\'identité', 'required' => false])
            ->add('date_expiration_piece_identite',   BirthdayType::class, ['label' => 'Date d\'expiration de la pièce d\'identité', 'required' => false])
            ->add('sexe',     ChoiceType::class, [
                'label'    => 'Sexe',
                'required' => false,
                'choices'  => $this->getChoices()
            ])
            ->add('civilite',     ChoiceType::class, [
                'label' => 'Civilité',
                'required' => false,
                'choices' => [
                    "M"   => "Monsieur",
                    "Mme" => "Madame",
                    "Mlle" => "Mademoiselle",
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Sélectionner une image',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // everytime you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        // 'maxWidth' => '500',
                        // 'maxWidthMessage' => 'La largeur de l\'image ne doit pas dépasser 500px',
                        // 'maxHeight' => '500',
                        // 'maxHeightMessage' => 'La hauteur de l\'image ne doit pas dépasser 500px',

                        'mimeTypes' => [
                            'image/*'
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner une image',
                    ])
                ],
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
                    ->add('acompte',   NumberType::class, ['label' => 'Acomptes ou avances recçu', 'required' => true])
                    ->add('creance_initiale',   NumberType::class, ['label' => 'Créances à l\'enregistrement', 'required' => true])
                ;
            }
            else{
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }

    public function getChoices()
    {
      $choices = Customer::SEXE;
      $output = [];
      foreach($choices as $k => $v){
        $output[$v] = $k;
      }
      return $output;
    }
}
