<?php

namespace App\Http\Controllers\Compliance;

use App\Contracts\Kyc\AmlProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ComplianceController extends Controller
{
    public function __construct(private readonly AmlProviderInterface $aml) {}

    /** POST /api/admin/compliance/screen/{userId} */
    public function screenUser(Request $request, string $userId): JsonResponse
    {
        $user = AppUser::findOrFail($userId);

        $fullName = trim("{$user->first_name} {$user->last_name}");

        $pep        = $this->aml->screenPep($fullName, $user->date_of_birth ?? '', 'NG');
        $sanctions  = $this->aml->screenSanctions($fullName, 'NG');

        $result = [
            'user_id'        => $user->id,
            'full_name'      => $fullName,
            'pep_result'     => $pep,
            'sanctions_result' => $sanctions,
            'screened_at'    => now()->toIso8601String(),
            'provider'       => $this->aml->getProviderName(),
        ];

        return response()->json($result);
    }

    /** GET /api/admin/compliance/users */
    public function userList(Request $request): JsonResponse
    {
        $users = QueryBuilder::for(AppUser::class)
            ->allowedFilters(['status', 'kyc_status', 'tenant_id'])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->with('kycVerifications')
            ->paginate($request->input('per_page', 20));

        return response()->json($users);
    }
}
