<?php

namespace App\Form;

use App\Entity\Pelicula;
use App\Entity\Ranking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeliculaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('apiId')
            ->add('titulo')
            ->add('description')
            ->add('year')
            ->add('image_url')
            ->add('genre')
            ->add('stars')
            ->add('rankings', EntityType::class, [
                'class' => Ranking::class,
                'choice_label' => 'id',
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pelicula::class,
        ]);
    }
}
