<?php
namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{AgentDevice, Camera, Room};
use App\Services\CameraSetupPresetService;
use App\Services\OrganizationUsageService;

class CamerasController extends Controller
{
    public function __construct(
        protected OrganizationUsageService $usageService,
        protected CameraSetupPresetService $presetService,
    )
    {
    }

    public function index() {
        $org = Auth::user()->organization_id;
        $cameras = Camera::where('organization_id',$org)
            ->with(['room', 'agentDevice'])
            ->orderBy('id','desc')
            ->paginate(12);
        return view('dashboard.cameras.index', compact('cameras'));
    }
    public function create() {
        $organization = $this->organization();
        $orgId = $organization->id;
        $rooms = Room::where('organization_id', $orgId)->get();
        $agentDevices = AgentDevice::where('organization_id', $orgId)->orderBy('name')->get();
        $usage = $this->usageService->summary($organization);
        $analysisModes = $this->presetService->modes();
        $defaultMode = 'desk_monitoring';

        return view('dashboard.cameras.create', [
            'rooms' => $rooms,
            'agentDevices' => $agentDevices,
            'usage' => $usage,
            'analysisModes' => $analysisModes,
            'defaultMode' => $defaultMode,
            'defaultRoiJson' => $this->presetService->toPrettyJson($this->presetService->defaultRoi()),
            'defaultAnalysisConfigJson' => $this->presetService->toPrettyJson($this->presetService->defaultAnalysisConfig($defaultMode)),
        ]);
    }
    public function store(Request $r) {
        $r->validate([
            'room_id'=>'required|integer',
            'agent_device_id'=>'nullable|integer',
            'name'=>'required|string|max:255',
            'purpose'=>'required|string|max:100',
            'analysis_mode'=>'required|string|max:100',
            'rtsp_url'=>'nullable|string',
            'status'=>'nullable|string',
            'stream_status'=>'nullable|string|max:100',
            'health_message'=>'nullable|string',
            'roi'=>'nullable',
            'analysis_config'=>'nullable',
            'is_enabled'=>'nullable|boolean',
        ]);
        $organization = $this->organization();
        if (! $this->usageService->canCreate($organization, 'cameras')) {
            return redirect()
                ->route('subscription.index')
                ->withErrors(['plan_limit' => __('woork.limit_reached_message', ['resource' => __('woork.cameras')])]);
        }

        $orgId = $organization->id;
        Camera::create([
           'organization_id'=>$orgId,
           'agent_device_id'=>$this->resolveAgentDeviceId($r, $orgId),
           'room_id'=>$r->room_id,
           'name'=>$r->name,
           'purpose'=>$r->purpose,
           'analysis_mode'=>$r->analysis_mode,
           'rtsp_url'=>$r->rtsp_url,
           'status'=>$r->status,
           'stream_status'=>$r->stream_status,
           'health_message'=>$r->health_message,
           'is_enabled'=>$r->boolean('is_enabled', true),
           'roi'=>$this->decodeJsonField($r->roi),
           'analysis_config'=>$this->decodeJsonField($r->analysis_config),
        ]);
        return redirect()->route('cameras.index')->with('ok','Camera added');
    }
    public function edit($id) {
        $org = Auth::user()->organization_id;
        $camera = Camera::where('organization_id',$org)->findOrFail($id);
        $rooms = Room::where('organization_id',$org)->get();
        $agentDevices = AgentDevice::where('organization_id', $org)->orderBy('name')->get();
        $camera->roi = $camera->roi ? json_encode($camera->roi, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '';
        $camera->analysis_config = $camera->analysis_config
            ? json_encode($camera->analysis_config, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
            : '';
        $analysisModes = $this->presetService->modes();
        $defaultMode = $camera->analysis_mode ?: 'desk_monitoring';

        return view('dashboard.cameras.edit', [
            'camera' => $camera,
            'rooms' => $rooms,
            'agentDevices' => $agentDevices,
            'analysisModes' => $analysisModes,
            'defaultMode' => $defaultMode,
            'defaultRoiJson' => $this->presetService->toPrettyJson($this->presetService->defaultRoi()),
            'defaultAnalysisConfigJson' => $this->presetService->toPrettyJson($this->presetService->defaultAnalysisConfig($defaultMode)),
        ]);
    }
    public function update(Request $r, $id) {
        $org = Auth::user()->organization_id;
        $camera = Camera::where('organization_id',$org)->findOrFail($id);
        $r->validate([
            'room_id'=>'required|integer',
            'agent_device_id'=>'nullable|integer',
            'name'=>'required|string|max:255',
            'purpose'=>'required|string|max:100',
            'analysis_mode'=>'required|string|max:100',
            'rtsp_url'=>'nullable|string',
            'status'=>'nullable|string',
            'stream_status'=>'nullable|string|max:100',
            'health_message'=>'nullable|string',
            'roi'=>'nullable',
            'analysis_config'=>'nullable',
            'is_enabled'=>'nullable|boolean',
        ]);
        $camera->update([
            'agent_device_id'=>$this->resolveAgentDeviceId($r, $org),
            'room_id'=>$r->room_id,
            'name'=>$r->name,
            'purpose'=>$r->purpose,
            'analysis_mode'=>$r->analysis_mode,
            'rtsp_url'=>$r->rtsp_url,
            'status'=>$r->status,
            'stream_status'=>$r->stream_status,
            'health_message'=>$r->health_message,
            'is_enabled'=>$r->boolean('is_enabled', true),
            'roi'=>$this->decodeJsonField($r->roi),
            'analysis_config'=>$this->decodeJsonField($r->analysis_config),
        ]);
        return redirect()->route('cameras.index')->with('ok','Camera updated');
    }
    public function destroy($id) {
        $org = Auth::user()->organization_id;
        $cam = Camera::where('organization_id',$org)->findOrFail($id);
        $cam->delete();
        return redirect()->route('cameras.index')->with('ok','Camera deleted');
    }

    protected function resolveAgentDeviceId(Request $request, int $organizationId): ?int
    {
        $agentDeviceId = $request->input('agent_device_id');

        if (! $agentDeviceId) {
            return null;
        }

        return AgentDevice::where('organization_id', $organizationId)
            ->whereKey($agentDeviceId)
            ->value('id');
    }

    protected function decodeJsonField(mixed $value): ?array
    {
        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function organization(): Organization
    {
        return Organization::with(['plan', 'subscription.plan'])->findOrFail(Auth::user()->organization_id);
    }
}
