<?php

namespace App\Form;

use App\Entity\Mark;
use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextType::class, ['label' => 'Description', 'required' => true])
            ->add('unit_price', TextType::class, ['label' => 'Prix de vente unitaire', 'required' => true])
            ->add('purchasing_price', TextType::class, ['label' => 'Prix d\'achat', 'required' => true])
            ->add('security_stock', NumberType::class, ['label' => 'Stock de sécurité', 'required' => true])
            ->add('image', FileType::class, [
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
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            $product = $event->getData();
            $form = $event->getForm();

            if (!$product || null === $product->getId()) {
                $form
                ->add('reference',   TextType::class, ['label' => 'Référence', 'required' => true])
                ->add('category',    EntityType::class, [
                    'required' => true,
                    'class'    => Category::class,
                    'choice_label' => 'name',
                    'label'    => 'Catégorie',
                    'multiple' => false,
                    'placeholder' => 'Sélectionner un élément'
                ])
                ->add('mark',        EntityType::class, [
                    'required' => false,
                    'class'    => Mark::class,
                    'choice_label' => 'label',
                    'label'    => 'Marque',
                    'multiple' => false,
                    'placeholder' => 'Sélectionner un élément'
                ])
                ;
            }
            else{
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
