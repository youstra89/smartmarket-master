<?php

namespace App\Form;

use App\Entity\Informations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class InformationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType                   ::class, ['label' => 'Nom de l\'entreprise', 'required'      => true])
            ->add('regime_imposition', TextType     ::class, ['label' => 'Régime d\'imposition', 'required'      => false])
            ->add('telephone', TextType             ::class, ['label' => 'Numéro de téléphone', 'required'       => false])
            ->add('email', EmailType                ::class, ['label' => 'Email', 'required'                     => false])
            ->add('adresse', TextType               ::class, ['label' => 'Adresse', 'required'                   => false])
            ->add('capital', NumberType             ::class, ['label' => 'Capital', 'required'                   => false])
            ->add('site_web', TextType              ::class, ['label' => 'Site web', 'required'                  => false])
            ->add('numero_compte_bancaire', TextType::class, ['label' => 'Numéro de compte bancaire', 'required' => false])
            ->add('registre_commerce', TextType     ::class, ['label' => 'Immatriculation RCCM', 'required'      => false])
            ->add('slogan', TextType                ::class, ['label' => 'Slogan', 'required'                    => false])
            ->add('logo', FileType::class, [
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
                        'mimeTypes' => [
                            'image/*'
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner une image',
                    ])
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Informations::class,
        ]);
    }
}
