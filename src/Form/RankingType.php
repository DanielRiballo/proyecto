<?php

namespace App\Form;

use App\Entity\Pelicula;
use App\Entity\Ranking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RankingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre de la Categoría',
                'attr' => ['placeholder' => 'Ej: Mejores pelis de Drama']
            ])
            ->add('peliculas', EntityType::class, [
                'class' => Pelicula::class,
                'choice_label' => 'titulo',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Selecciona las películas para este Ranking',
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ranking::class,
        ]);
    }
}
