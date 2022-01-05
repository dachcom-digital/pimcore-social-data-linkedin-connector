<?php

namespace SocialData\Connector\LinkedIn\Form\Admin\Type;

use SocialData\Connector\LinkedIn\Model\FeedConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkedInFeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('companyId', TextType::class);
        $builder->add('limit', IntegerType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => FeedConfiguration::class
        ]);
    }
}
