<?php

namespace App\Libraries;

class ServiceInstructionResolver
{
    public static function resolve(array $pair, array $serviceRules): array
    {
        $useAssetDestination = ! array_key_exists('use_asset_destination', $serviceRules)
            || (bool) $serviceRules['use_asset_destination'] === true;
        $assetDestinationNumber = trim((string) ($pair['to_destination_account_number'] ?? ''));
        $assetDestinationName = trim((string) ($pair['to_destination_account_name'] ?? ''));
        $overrideDestinationNumber = trim((string) ($serviceRules['destination_number'] ?? ''));
        $overrideDestinationName = trim((string) ($serviceRules['destination_name'] ?? ''));

        $effectiveDestinationNumber = $useAssetDestination && $assetDestinationNumber !== ''
            ? $assetDestinationNumber
            : $overrideDestinationNumber;
        $effectiveDestinationName = $useAssetDestination && $assetDestinationName !== ''
            ? $assetDestinationName
            : $overrideDestinationName;

        $resolved = $serviceRules;
        $resolved['use_asset_destination'] = $useAssetDestination;
        if ($effectiveDestinationNumber !== '') {
            $resolved['effective_destination_number'] = $effectiveDestinationNumber;
        }
        if ($effectiveDestinationName !== '') {
            $resolved['effective_destination_name'] = $effectiveDestinationName;
        }

        return $resolved;
    }
}
