<?php

namespace SocialData\Connector\LinkedIn\Model;

use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;
use SocialData\Connector\LinkedIn\Form\Admin\Type\LinkedInFeedType;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    protected ?string $companyId = null;
    protected ?int $limit = null;

    public static function getFormClass(): string
    {
        return LinkedInFeedType::class;
    }

    public function setCompanyId(?string $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
