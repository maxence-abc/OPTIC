<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Notez votre expérience',
                'choices' => [
                    '5' => 5,
                    '4' => 4,
                    '3' => 3,
                    '2' => 2,
                    '1' => 1,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Laissez un commentaire',
                'required' => true,
                'attr' => [
                    'class' => 'review-form__textarea',
                    'rows' => 4,
                    'maxlength' => 1500,
                    'placeholder' => 'Votre avis...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
