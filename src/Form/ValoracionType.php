<?php

namespace App\Form;

use App\Entity\Valoracion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValoracionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('puntuacion', ChoiceType::class, [
                'label' => 'Tu puntuación',
                'choices'  => [
                    '⭐⭐⭐⭐⭐ Excelente' => 5,
                    '⭐⭐⭐⭐ Muy buena' => 4,
                    '⭐⭐⭐ Normal' => 3,
                    '⭐⭐ Regular' => 2,
                    '⭐ Mala' => 1,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('comentario', TextareaType::class, [
                'label' => 'Tu opinión',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Valoracion::class,
        ]);
    }
}
