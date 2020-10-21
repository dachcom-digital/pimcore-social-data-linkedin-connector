<?php

namespace SocialData\Connector\LinkedIn\Model;

use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;
use SocialData\Connector\LinkedIn\Form\Admin\Type\LinkedInFeedType;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    /**
     * @var string|null
     */
    protected $companyId;

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
     * @param string|null $companyId
     */
    public function setCompanyId(?string $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * @return string|null
     */
    public function getCompanyId()
    {
        return $this->companyId;
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
