<?php

namespace SocialData\Connector\LinkedIn\Builder;

use SocialData\Connector\LinkedIn\Client\LinkedInClient;
use SocialDataBundle\Dto\BuildConfig;
use SocialData\Connector\LinkedIn\Model\EngineConfiguration;
use SocialData\Connector\LinkedIn\Model\FeedConfiguration;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialPostBuilder implements SocialPostBuilderInterface
{
    /**
     * @var LinkedInClient
     */
    protected $linkedInClient;

    /**
     * @param LinkedInClient $linkedInClient
     */
    public function __construct(LinkedInClient $linkedInClient)
    {
        $this->linkedInClient = $linkedInClient;
    }

    /**
     * {@inheritDoc}
     */
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        $engineConfiguration = $buildConfig->getEngineConfiguration();
        $feedConfiguration = $buildConfig->getFeedConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        if (empty($feedConfiguration->getCompanyId())) {
            return;
        }

        $client = $this->linkedInClient->getClient($engineConfiguration);
        $client->setAccessToken($engineConfiguration->getAccessToken());

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 2;

        /**
         * @see https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/getting-started
         */
        $endPoint = 'ugcPosts';

        $payload = [
            'q'       => 'authors',
            'authors' => sprintf('List(urn:li:organization:%s)', $feedConfiguration->getCompanyId()),
            'count'   => $limit
        ];

        $resolver->setDefaults([
            'queryEndPoint' => $endPoint,
            'queryPayLoad'  => $payload,
        ]);

        $resolver->setRequired(['queryEndPoint', 'queryPayLoad']);
        $resolver->addAllowedTypes('queryEndPoint', ['string']);
        $resolver->addAllowedTypes('queryPayLoad', ['array']);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $engineConfiguration = $buildConfig->getEngineConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        if (empty($options)) {
            return;
        }

        $client = $this->linkedInClient->getClient($engineConfiguration);
        $client->setAccessToken($engineConfiguration->getAccessToken());

        $queryEndPoint = $options['queryEndPoint'];
        $queryPayLoad = $options['queryPayLoad'];

        try {
            $response = $client->get($queryEndPoint, $queryPayLoad);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('fetch error: %s [endpoint: %s]', $e->getMessage(), $queryEndPoint));
        }

        // @todo
        //$data->setFetchedEntities([]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
    public function filter(FilterData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();

        if (!is_array($element)) {
            return;
        }

        // @todo

    }

    /**
     * {@inheritDoc}
     */
    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
    public function transform(TransformData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();
        $socialPost = $data->getSocialPostEntity();

        if (!is_array($element)) {
            return;
        }

        // @todo
    }
}
