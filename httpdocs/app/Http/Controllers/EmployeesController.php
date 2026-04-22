<?php
namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Employee,Room};
use App\Services\OrganizationUsageService;

class EmployeesController extends Controller
{
    public function __construct(protected OrganizationUsageService $usageService)
    {
    }

    public function index() {
        $org = Auth::user()->organization_id;
        $employees = Employee::where('organization_id',$org)->with('room')->orderBy('id','desc')->paginate(12);
        return view('dashboard.employees.index', compact('employees'));
    }
    public function create() {
        $organization = $this->organization();
        $rooms = Room::where('organization_id',$organization->id)->get();
        $usage = $this->usageService->summary($organization);
        return view('dashboard.employees.create', compact('rooms', 'usage'));
    }
    public function store(Request $r) {
        $r->validate([
            'room_id'=>'required|integer',
            'name'=>'required|string|max:255',
            'title'=>'nullable|string',
            'photos'=>'nullable',
            'is_active'=>'nullable|boolean'
        ]);
        $organization = $this->organization();
        if (! $this->usageService->canCreate($organization, 'employees')) {
            return redirect()
                ->route('subscription.index')
                ->withErrors(['plan_limit' => __('woork.limit_reached_message', ['resource' => __('woork.employees')])]);
        }

        $photos = $r->photos ? json_decode($r->photos, true) : null;
        Employee::create([
            'organization_id'=>$organization->id,
            'room_id'=>$r->room_id,
            'name'=>$r->name,
            'title'=>$r->title,
            'photos'=>$photos,
            'is_active'=>$r->boolean('is_active', True)
        ]);
        return redirect()->route('employees.index')->with('ok','Employee created');
    }
    public function edit($id) {
        $org = Auth::user()->organization_id;
        $emp = Employee::where('organization_id',$org)->findOrFail($id);
        $rooms = Room::where('organization_id',$org)->get();
        $emp->photos = $emp->photos ? json_encode($emp->photos, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '';
        return view('dashboard.employees.edit', compact('emp','rooms'));
    }
    public function update(Request $r, $id) {
        $org = Auth::user()->organization_id;
        $emp = Employee::where('organization_id',$org)->findOrFail($id);
        $r->validate([
            'room_id'=>'required|integer',
            'name'=>'required|string|max:255',
            'title'=>'nullable|string',
            'photos'=>'nullable',
            'is_active'=>'nullable|boolean'
        ]);
        $emp->update([
            'room_id'=>$r->room_id,
            'name'=>$r->name,
            'title'=>$r->title,
            'photos'=>$r->photos ? json_decode($r->photos, true) : null,
            'is_active'=>$r->boolean('is_active', True)
        ]);
        return redirect()->route('employees.index')->with('ok','Employee updated');
    }
    public function destroy($id) {
        $org = Auth::user()->organization_id;
        $emp = Employee::where('organization_id',$org)->findOrFail($id);
        $emp->delete();
        return redirect()->route('employees.index')->with('ok','Employee deleted');
    }

    protected function organization(): Organization
    {
        return Organization::with(['plan', 'subscription.plan'])->findOrFail(Auth::user()->organization_id);
    }
}
