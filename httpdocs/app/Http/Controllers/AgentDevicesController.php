<?php

namespace App\Http\Controllers;

use App\Models\AgentDevice;
use App\Models\AgentRelease;
use App\Models\Organization;
use App\Services\OrganizationOnboardingService;
use App\Services\AgentValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\OrganizationUsageService;

class AgentDevicesController extends Controller
{
    public function __construct(protected OrganizationUsageService $usageService)
    {
    }

    public function index(): View
    {
        $organizationId = Auth::user()->organization_id;

        $devices = AgentDevice::where('organization_id', $organizationId)
            ->withCount('cameras')
            ->with('heartbeats')
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('dashboard.agent-devices.index', compact('devices'));
    }

    public function create(): View
    {
        $usage = $this->usageService->summary($this->organization());

        return view('dashboard.agent-devices.create', compact('usage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'os' => 'nullable|string|max:100',
            'version' => 'nullable|string|max:50',
        ]);

        $organization = $this->organization();
        if (! $this->usageService->canCreate($organization, 'agent_devices')) {
            return redirect()
                ->route('subscription.index')
                ->withErrors(['plan_limit' => __('woork.limit_reached_message', ['resource' => __('dashboard.agent_devices_title')])]);
        }

        $device = AgentDevice::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'device_uuid' => 'pending-'.Str::uuid(),
            'pairing_token' => $this->newPairingToken(),
            'status' => 'pending',
            'os' => $data['os'] ?? 'windows',
            'version' => $data['version'] ?? '1.0.0',
            'is_active' => true,
        ]);

        return redirect()
            ->route('agent-devices.show', $device)
            ->with('ok', __('dashboard.agent_device_created'));
    }

    public function show(AgentDevice $agentDevice): View
    {
        $this->authorizeDevice($agentDevice);

        $agentDevice->load([
            'cameras.room',
            'heartbeats' => fn ($query) => $query->latest('checked_at')->limit(10),
            'cameraHeartbeats.camera' => fn ($query) => $query->latest('checked_at')->limit(20),
        ]);

        $recentCameraHeartbeats = $agentDevice->cameraHeartbeats
            ->sortByDesc('checked_at')
            ->take(12)
            ->values();

        $downloadUrl = route('agent-devices.install', $agentDevice);
        $registerEndpoint = url('/api/agent/register');
        $configEndpoint = url('/api/agent/config');
        $onboarding = app(OrganizationOnboardingService::class)->summary($this->organization());

        return view('dashboard.agent-devices.show', compact(
            'agentDevice',
            'downloadUrl',
            'registerEndpoint',
            'configEndpoint',
            'recentCameraHeartbeats',
            'onboarding',
        ));
    }

    public function install(AgentDevice $agentDevice): View
    {
        $this->authorizeDevice($agentDevice);

        $stableReleases = AgentRelease::publishedStableByPlatform()->keyBy('platform');
        $fallbackArtifacts = [
            'windows-x64' => 'downloads/woork-agent-windows-x64.zip',
            'windows-x86' => 'downloads/woork-agent-windows-x86.zip',
            'windows-7-legacy' => 'downloads/WoorkAgentSetup-LegacyWin7-1.0.0.exe',
        ];

        $downloadVariants = collect(AgentRelease::supportedPlatforms())
            ->map(function (array $meta, string $platform) use ($stableReleases, $fallbackArtifacts) {
                $release = $stableReleases->get($platform);
                $fallbackPath = $fallbackArtifacts[$platform] ?? null;
                $publicFallbackPath = $fallbackPath ? public_path($fallbackPath) : null;
                $hasFallbackArtifact = $publicFallbackPath && file_exists($publicFallbackPath);
                $artifactPath = $release?->artifact_path ?? ($hasFallbackArtifact ? $fallbackPath : null);

                if (! $artifactPath) {
                    return null;
                }

                $artifactSize = $release?->artifact_size;
                if (! $artifactSize && $hasFallbackArtifact) {
                    $artifactSize = filesize($publicFallbackPath);
                }

                return [
                    'platform' => $platform,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'download_url' => asset($artifactPath),
                    'artifact_path' => $artifactPath,
                    'artifact_size' => $artifactSize,
                    'release' => $release,
                    'is_legacy' => $platform === 'windows-7-legacy',
                    'is_primary' => $platform === 'windows-x64',
                ];
            })
            ->filter()
            ->values();

        $primaryVariant = $downloadVariants->firstWhere('is_primary', true) ?? $downloadVariants->first();

        return view('dashboard.agent-devices.install', [
            'agentDevice' => $agentDevice,
            'release' => $primaryVariant['release'] ?? null,
            'onboarding' => app(OrganizationOnboardingService::class)->summary($this->organization()),
            'downloadVariants' => $downloadVariants,
            'primaryVariant' => $primaryVariant,
            'registerEndpoint' => url('/api/agent/register'),
            'configEndpoint' => url('/api/agent/config'),
            'heartbeatEndpoint' => url('/api/agent/heartbeat'),
            'ingestEndpoint' => url('/api/agent/ingest'),
        ]);
    }

    public function validation(AgentDevice $agentDevice): View
    {
        $this->authorizeDevice($agentDevice);

        $agentDevice->load([
            'cameras.room',
            'heartbeats' => fn ($query) => $query->latest('checked_at')->limit(10),
            'cameraHeartbeats.camera' => fn ($query) => $query->latest('checked_at')->limit(20),
        ]);

        $validation = app(AgentValidationService::class)->summary($agentDevice);

        return view('dashboard.agent-devices.validation', [
            'agentDevice' => $agentDevice,
            'validation' => $validation,
        ]);
    }

    public function rotatePairingToken(AgentDevice $agentDevice): RedirectResponse
    {
        $this->authorizeDevice($agentDevice);

        $agentDevice->update([
            'pairing_token' => $this->newPairingToken(),
            'api_token_hash' => null,
            'status' => 'pending',
        ]);

        return back()->with('ok', __('dashboard.agent_token_rotated'));
    }

    protected function authorizeDevice(AgentDevice $agentDevice): void
    {
        abort_unless($agentDevice->organization_id === Auth::user()->organization_id, 404);
    }

    protected function newPairingToken(): string
    {
        return 'PAIR-'.Str::upper(Str::random(6)).'-'.Str::upper(Str::random(6));
    }

    protected function organization(): Organization
    {
        return Organization::with(['plan', 'subscription.plan'])->findOrFail(Auth::user()->organization_id);
    }
}
