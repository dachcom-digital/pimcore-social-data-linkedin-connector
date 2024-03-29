<?php

namespace SocialData\Connector\LinkedIn\Form\Admin\Type;

use SocialData\Connector\LinkedIn\Model\EngineConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkedInEngineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('accessToken', TextType::class);
        $builder->add('accessTokenExpiresAt', TextType::class, ['required' => false]);
        $builder->add('clientId', TextType::class);
        $builder->add('clientSecret', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class'      => EngineConfiguration::class
        ]);
    }
}
