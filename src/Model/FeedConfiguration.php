<?php

namespace SocialData\Connector\LinkedIn\Model;

use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;
use SocialData\Connector\LinkedIn\Form\Admin\Type\LinkedInFeedType;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    /**
     * @var string|null
     */
    protected $pageId;

    /**
     * @var int
     */
    protected $limit;

    /**
     * {@inheritdoc}
     */
    public static function getFormClass()
    {
        return LinkedInFeedType::class;
    }

    /**
     * @param string|null $pageId
     */
    public function setPageId(?string $pageId)
    {
        $this->pageId = $pageId;
    }

    /**
     * @return string|null
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
