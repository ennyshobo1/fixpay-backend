<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /** GET /api/portal/webhooks */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('__tenant_id');

        return response()->json(
            WebhookEndpoint::where('tenant_id', $tenantId)->get(['id', 'url', 'events', 'environment', 'active', 'failure_count', 'last_triggered_at'])
        );
    }

    /** POST /api/portal/webhooks */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url'         => 'required|url',
            'events'      => 'required|array',
            'events.*'    => 'string',
            'environment' => 'required|in:TEST,LIVE',
        ]);

        $tenantId = $request->input('__tenant_id');
        $endpoint = WebhookEndpoint::create([
            'tenant_id'      => $tenantId,
            'url'            => $data['url'],
            'events'         => $data['events'],
            'signing_secret' => Str::random(32),
            'environment'    => $data['environment'],
            'active'         => true,
            'failure_count'  => 0,
        ]);

        return response()->json([
            'id' => $endpoint->id,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'signing_secret' => $endpoint->signing_secret, // shown once
        ], 201);
    }

    /** DELETE /api/portal/webhooks/{id} */
    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->input('__tenant_id');
        WebhookEndpoint::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail()->delete();

        return response()->json(['message' => 'Webhook deleted.']);
    }
}
