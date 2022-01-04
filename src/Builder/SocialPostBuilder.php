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
    protected LinkedInClient $linkedInClient;

    public function __construct(LinkedInClient $linkedInClient)
    {
        $this->linkedInClient = $linkedInClient;
    }

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

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 20;

        /**
         * @see https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/getting-started
         */
        $endPoint = 'ugcPosts';

        /**
         * @see https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/ugc-post-api#find-ugc-posts-by-authors
         */
        $payload = [
            'q'       => 'authors',
            'authors' => sprintf('List(%s)', urlencode(sprintf('urn:li:organization:%s', $feedConfiguration->getCompanyId()))),
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
            $response = $client->getEncoded($queryEndPoint, $queryPayLoad);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('fetch error: %s [endpoint: %s]', $e->getMessage(), $queryEndPoint));
        }

        if (!is_array($response)) {
            return;
        }

        if (!isset($response['elements'])) {
            return;
        }

        $elements = $response['elements'];

        if (!is_array($elements)) {
            return;
        }

        $data->setFetchedEntities($elements);
    }

    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    public function filter(FilterData $data): void
    {
        $element = $data->getTransferredData();

        if (!is_array($element)) {
            return;
        }

        $lifecycleState = $element['lifecycleState'] ?? null;
        $visibility = $element['visibility'] ?? null;

        if (!in_array($lifecycleState, ['PUBLISHED', 'PUBLISHED_EDITED'])) {
            return;
        }

        if (!isset($element['id'])) {
            return;
        }

        if (is_array($visibility) && !in_array('PUBLIC', array_values($visibility), true)) {
            return;
        }

        $data->setFilteredElement($element);
        $data->setFilteredId($element['id']);
    }

    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    public function transform(TransformData $data): void
    {
        $element = $data->getTransferredData();
        $socialPost = $data->getSocialPostEntity();

        if (!is_array($element)) {
            return;
        }

        $thumbnail = null;
        $id = $element['id'];
        $content = $element['specificContent'];
        $firstPublishedAt = $element['firstPublishedAt'];

        $shareContent = $content['com.linkedin.ugc.ShareContent'];
        $shareCommentary = $shareContent['shareCommentary'];
        $media = $shareContent['media'];
        $text = $shareCommentary['text'];
        $inferredLocale = $shareCommentary['inferredLocale'];
        $attributes = $shareCommentary['attributes'];

        if (!empty($firstPublishedAt)) {
            $creationTime = Carbon::createFromTimestamp($firstPublishedAt / 1000);
        } else {
            $creationTime = Carbon::now();
        }

        $socialPost->setSocialCreationDate($creationTime);
        $socialPost->setContent($text);
        $socialPost->setUrl(sprintf('https://www.linkedin.com/feed/update/%s', $id));

        $thumbnail = $this->findThumbnail($media);

        if ($thumbnail !== null) {
            $socialPost->setPosterUrl($thumbnail);
        }

        $data->setTransformedElement($socialPost);
    }

    protected function findThumbnail(mixed $media): ?string
    {
        if (!is_array($media)) {
            return null;
        }

        $thumbnail = null;

        foreach ($media as $shareMedia) {

            if (!isset($shareMedia['thumbnails']) || !is_array($shareMedia['thumbnails'])) {
                continue;
            }

            $validThumbs = array_filter($shareMedia['thumbnails'], function ($t) {
                return isset($t['width']);
            });

            if (count($validThumbs) === 0) {
                continue;
            }

            $widthColumns = array_column($validThumbs, 'width');
            $index = array_keys($widthColumns, max($widthColumns));

            if (isset($index[0], $validThumbs[$index[0]]['url'])) {
                $thumbnail = $validThumbs[$index[0]]['url'];
                break;
            }
        }

        return $thumbnail;
    }
}
