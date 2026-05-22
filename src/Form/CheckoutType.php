<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone number',
                'attr' => ['placeholder' => '09XX XXX XXXX'],
                'constraints' => [
                    new NotBlank(message: 'Please enter your phone number.'),
                    new Length(max: 20),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order notes (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'Delivery instructions, size notes, etc.', 'rows' => 3],
            ])
        ;
    }
}
