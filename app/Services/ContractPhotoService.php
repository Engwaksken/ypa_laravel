<?php

namespace App\Services;

use App\Models\Contract;

class ContractPhotoService
{
    public function resolve(Contract $contract, bool $browser = false): array
    {
        $member = $contract->member;
        $kin = $member?->nextOfKin ?? collect();
        $partySources = [$contract->farmer_photo, $member?->member_photo, $member?->photo, $member?->photo_path, $member?->image_url];
        $kinSources = [$contract->nok_photo];
        foreach ($kin as $person) {
            foreach (['photo', 'nok_photo', 'photo_path', 'image_url'] as $field) $kinSources[] = $person->{$field};
        }
        foreach (['next_of_kin_photo', 'next_of_kin_photo_url', 'nok_photo', 'nok_photo_url', 'next_kin_photo', 'next_kin_photo_url', 'guardian_photo', 'guardian_photo_url', 'guardian_id_photo', 'nok_id_photo', 'next_of_kin_id_photo', 'kin_photo', 'kin_photo_url'] as $field) {
            $kinSources[] = $member?->{$field};
        }

        return ['partyPhoto' => $this->firstImage($partySources, $browser), 'kinPhoto' => $this->firstImage($kinSources, $browser)];
    }

    private function firstImage(array $sources, bool $browser): ?string
    {
        $assets = app(PdfAssetService::class);
        foreach ($sources as $source) {
            $uri = $browser ? $assets->browserImageUri($source) : $assets->localImageUri($source);
            if ($uri) return $uri;
        }
        return null;
    }
}
