<?php

namespace SocialData\Connector\LinkedIn\Builder;

use Carbon\Carbon;
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
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $engineConfiguration = $buildConfig->getEngineConfiguration();
        $feedConfiguration = $buildConfig->getFeedConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        $client = $this->linkedInClient->getClient($engineConfiguration);
        $client->setAccessToken($engineConfiguration->getAccessToken());

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 2;

        $endPoint = 'ugcPosts';

        $payload = [
            'ids'   => 'List({encoded ugcPostUrn},{encoded ugcPostUrn})',
            'count' => $limit
        ];

        try {
            $response = $client->get($endPoint, $payload);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('fetch error: %s [endpoint: %s]', $e->getMessage(), $endPoint));
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
