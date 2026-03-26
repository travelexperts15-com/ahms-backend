<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/settings — optionally filter by ?group=
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::when($request->group, fn($q, $v) => $q->where('group', $v))
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(fn($s) => [
                'key'         => $s->key,
                'value'       => $s->typed_value,
                'group'       => $s->group,
                'type'        => $s->type,
                'label'       => $s->label,
                'description' => $s->description,
            ]);

        return $this->success($settings, 'Settings retrieved.');
    }

    // PUT /api/settings — bulk update: body is { key: value, ... }
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings'         => ['required', 'array'],
            'settings.*.key'   => ['required', 'string', 'exists:settings,key'],
            'settings.*.value' => ['nullable'],
        ]);

        foreach ($request->settings as $item) {
            Setting::set($item['key'], $item['value']);
        }

        // Flush group caches
        Cache::flush();

        $this->audit->log(
            event:       'settings.updated',
            description: 'System settings updated.',
            userId:      $request->user()->id,
            properties:  ['keys' => collect($request->settings)->pluck('key')],
        );

        return $this->success(null, 'Settings updated successfully.');
    }

    // GET /api/settings/{key}
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return $this->notFound('Setting not found.');
        }

        return $this->success([
            'key'   => $setting->key,
            'value' => $setting->typed_value,
            'group' => $setting->group,
            'type'  => $setting->type,
        ], 'Setting retrieved.');
    }
}
