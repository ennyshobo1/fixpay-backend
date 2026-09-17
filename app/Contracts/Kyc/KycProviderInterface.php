<?php

namespace App\Contracts\Kyc;

interface KycProviderInterface
{
    /**
     * Verify BVN (Bank Verification Number).
     *
     * @return array{status: bool, reference: string, data: array}
     */
    public function verifyBvn(string $bvn, string $dob): array;

    /**
     * Verify NIN (National Identification Number).
     *
     * @return array{status: bool, reference: string, data: array}
     */
    public function verifyNin(string $nin): array;

    /**
     * Verify CAC registration number.
     *
     * @return array{status: bool, reference: string, data: array}
     */
    public function verifyCac(string $rcNumber): array;

    /**
     * Match face (selfie) against a reference document.
     *
     * @return array{status: bool, confidence: float, reference: string}
     */
    public function matchFace(string $imageBase64, string $referenceId): array;

    public function getProviderName(): string;
}
