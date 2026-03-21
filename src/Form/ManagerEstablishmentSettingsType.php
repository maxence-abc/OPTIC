<?php

namespace App\Form;

use App\Entity\Establishment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ManagerEstablishmentSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => true,
                'placeholder' => 'Choisir une catégorie',
                'choices' => [
                    'Restaurant' => 'restaurant',
                    'Coiffure' => 'coiffure',
                    'Santé' => 'sante',
                    'Sport' => 'sport',
                    'Automobile' => 'automobile',
                    'Bien-être' => 'bien-etre',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('professionalEmail', EmailType::class, [
                'label' => 'Email professionnel',
                'required' => false,
            ])
            ->add('professionalPhone', TextType::class, [
                'label' => 'Téléphone professionnel',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->add('photos', FileType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'label' => 'Ajouter des photos',
                'help' => 'Vous pouvez sélectionner plusieurs images à la fois.',
                'attr' => [
                    'multiple' => true,
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'constraints' => [
                    new Assert\All([
                        new Assert\Image([
                            'maxSize' => '12M',
                            'maxSizeMessage' => 'Chaque image doit faire moins de {{ limit }}.',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP.',
                        ]),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Establishment::class,
        ]);
    }
}
