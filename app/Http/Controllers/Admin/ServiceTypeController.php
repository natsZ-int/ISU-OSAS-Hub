<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceTypeRequest;
use App\Http\Requests\UpdateServiceTypeRequest;
use App\Models\ServiceType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceType::withCount('serviceRequests');

        if ($search = $request->filled('search') ? trim((string) $request->input('search')) : null) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('instructions', 'like', $like);
            });
        }

        $serviceTypes = $query->orderBy('display_order')->get();

        return view('admin.services.types.index', compact('serviceTypes'));
    }

    public function create()
    {
        return view('admin.services.types.create');
    }

    public function store(StoreServiceTypeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $serviceType = ServiceType::create([
                'name' => $request->name,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'is_active' => $request->is_active,
                'display_order' => $request->display_order,
            ]);

            if ($request->has('requirements')) {
                foreach ($request->requirements as $req) {
                    $serviceType->requirements()->create([
                        'name' => $req['name'],
                        'description' => $req['description'] ?? null,
                        'is_required' => (bool) $req['is_required'],
                        'display_order' => (int) $req['display_order'],
                    ]);
                }
            }

            AuditLogService::log(
                action: 'Created Service Type',
                module: 'Student Services',
                recordType: 'ServiceType',
                recordId: $serviceType->id,
                description: "Service Type [{$serviceType->name}] created."
            );
        });

        return redirect()->route('admin.service-types.index')->with('status', 'Service type created successfully.');
    }

    public function edit(ServiceType $serviceType)
    {
        $serviceType->load('requirements');

        return view('admin.services.types.edit', compact('serviceType'));
    }

    public function update(UpdateServiceTypeRequest $request, ServiceType $serviceType)
    {
        DB::transaction(function () use ($request, $serviceType) {
            $serviceType->update([
                'name' => $request->name,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'is_active' => $request->is_active,
                'display_order' => $request->display_order,
            ]);

            $keptIds = [];
            if ($request->has('requirements')) {
                foreach ($request->requirements as $req) {
                    if (! empty($req['id'])) {
                        $existing = $serviceType->requirements()->find($req['id']);
                        if ($existing) {
                            $existing->update([
                                'name' => $req['name'],
                                'description' => $req['description'] ?? null,
                                'is_required' => (bool) $req['is_required'],
                                'display_order' => (int) $req['display_order'],
                            ]);
                            $keptIds[] = $existing->id;
                        }
                    } else {
                        $new = $serviceType->requirements()->create([
                            'name' => $req['name'],
                            'description' => $req['description'] ?? null,
                            'is_required' => (bool) $req['is_required'],
                            'display_order' => (int) $req['display_order'],
                        ]);
                        $keptIds[] = $new->id;
                    }
                }
            }

            $serviceType->requirements()->whereNotIn('id', $keptIds)->delete();

            AuditLogService::log(
                action: 'Updated Service Type',
                module: 'Student Services',
                recordType: 'ServiceType',
                recordId: $serviceType->id,
                description: "Service Type [{$serviceType->name}] updated."
            );
        });

        return redirect()->route('admin.service-types.index')->with('status', 'Service type updated successfully.');
    }

    public function toggle(ServiceType $serviceType)
    {
        $serviceType->update(['is_active' => ! $serviceType->is_active]);

        $statusLabel = $serviceType->is_active ? 'activated' : 'deactivated';

        AuditLogService::log(
            action: "Service Type {$statusLabel}",
            module: 'Student Services',
            recordType: 'ServiceType',
            recordId: $serviceType->id,
            description: "Service Type [{$serviceType->name}] was {$statusLabel}."
        );

        return redirect()->back()->with('status', "Service type {$statusLabel} successfully.");
    }
}
