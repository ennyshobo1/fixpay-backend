<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletFundingService;
use Illuminate\Http\Request;

class FundWalletController extends Controller
{
    public function __construct(protected WalletFundingService $walletFundingService)
    {
        $this->walletFundingService = $walletFundingService;
    }

    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        return $this->walletFundingService
            ->initialize(
                $request->user(),
                $validated['amount']
            );
    }
}
