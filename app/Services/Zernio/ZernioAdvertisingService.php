<?php

declare(strict_types=1);

namespace App\Services\Zernio;

final class ZernioAdvertisingService
{
    public function __construct(private readonly ZernioClient $client) {}

    /** @return array<string, mixed> */
    public function listAds(array $filters = []): array
    {
        return $this->client->listAds($this->withProfile($filters));
    }

    /** @return array<string, mixed> */
    public function campaignAnalytics(string $campaignId, array $filters = []): array
    {
        return $this->client->getCampaignAnalytics($campaignId, $this->withProfile($filters));
    }

    /** @return array<string, mixed> */
    public function adAnalytics(string $adId, array $filters = []): array
    {
        return $this->client->getAdAnalytics($adId, $this->withProfile($filters));
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function withProfile(array $filters): array
    {
        if (! isset($filters['profileId']) && filled(config('services.zernio.profile_id'))) {
            $filters['profileId'] = config('services.zernio.profile_id');
        }

        return $filters;
    }
}
