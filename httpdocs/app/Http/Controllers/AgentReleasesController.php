<?php

namespace App\Http\Controllers;

use App\Models\AgentRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AgentReleasesController extends Controller
{
    public function index(): View
    {
        $releases = AgentRelease::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        $latestStable = AgentRelease::published()
            ->where('channel', 'stable')
            ->where('platform', 'windows-x64')
            ->latest('published_at')
            ->first();

        return view('dashboard.agent-releases.index', compact('releases', 'latestStable'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();
        return view('admin.agent-releases.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'version' => 'required|string|max:50|unique:agent_releases,version',
            'channel' => 'required|string|max:20',
            'platform' => 'required|string|max:50',
            'artifact' => 'nullable|file|max:512000',
            'artifact_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $artifact = $request->file('artifact');
        $artifactPath = $data['artifact_path'] ?? null;
        $artifactName = null;
        $checksum = null;
        $artifactSize = null;

        if ($artifact) {
            $storedPath = $artifact->storeAs(
                'agent-releases',
                $data['version'].'-'.$artifact->getClientOriginalName(),
                'public'
            );
            $artifactPath = 'storage/'.$storedPath;
            $artifactName = $artifact->getClientOriginalName();
            $checksum = hash_file('sha256', $artifact->getRealPath());
            $artifactSize = $artifact->getSize();
        }

        if (! $artifactPath) {
            return back()
                ->withInput()
                ->withErrors(['artifact' => __('dashboard.agent_release_artifact_required')]);
        }

        AgentRelease::create([
            'version' => $data['version'],
            'channel' => $data['channel'],
            'platform' => $data['platform'],
            'artifact_path' => $artifactPath,
            'artifact_name' => $artifactName,
            'checksum_sha256' => $checksum,
            'artifact_size' => $artifactSize,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'published_at' => $data['published_at'] ?? now(),
        ]);

        return redirect()->route('agent-releases.index')->with('ok', __('dashboard.agent_release_created'));
    }

    protected function authorizeAdmin(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'super_admin') {
            throw new HttpException(403, 'Forbidden');
        }
    }
}
