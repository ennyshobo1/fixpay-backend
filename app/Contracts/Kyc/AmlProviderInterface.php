<?php

namespace App\Contracts\Kyc;

interface AmlProviderInterface
{
    /**
     * Screen for Politically Exposed Person (PEP).
     *
     * @return array{status: bool, is_pep: bool, reference: string, matches: array}
     */
    public function screenPep(string $fullName, string $dob, string $country = 'NG'): array;

    /**
     * Screen against sanctions lists (OFAC, UN, EU, local).
     *
     * @return array{status: bool, is_sanctioned: bool, reference: string, matches: array}
     */
    public function screenSanctions(string $fullName, string $country = 'NG'): array;

    public function getProviderName(): string;
}
