<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class FamilyChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Formulaire minimal : une seule question avec 3 choix
        $builder->add('composition', ChoiceType::class, [
            'label' => 'Situation familiale',
            // Les clés sont les labels affichés, les valeurs sont ce qu'on stocke
            'choices' => [
                'Bébé' => 'bebe',
                'Adolescent' => 'ado',
                'Les deux' => 'les_deux',
            ],
            // Afficher sous forme de boutons radio
            'expanded' => true,
            'multiple' => false,
        ]);
    }
}
