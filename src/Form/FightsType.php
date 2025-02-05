<?php

namespace App\Form;

use App\Entity\Fights;
use App\Entity\Pokemons;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FightsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('result')
            ->add('pokeuser', EntityType::class, [
                'class' => Pokemons::class,
                'choice_label' => 'id',
            ])
            ->add('pokenemy', EntityType::class, [
                'class' => Pokemons::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Fights::class,
        ]);
    }
}
