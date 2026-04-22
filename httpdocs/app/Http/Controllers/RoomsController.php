<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;

class RoomsController extends Controller
{
    public function index() {
        $org = Auth::user()->organization_id;
        $rooms = Room::where('organization_id', $org)->orderBy('id','desc')->paginate(12);
        return view('dashboard.rooms.index', compact('rooms'));
    }
    public function create() {
        return view('dashboard.rooms.create');
    }
    public function store(Request $r) {
        $r->validate(['name'=>'required|string|max:255','location'=>'nullable|string','notes'=>'nullable|string']);
        Room::create([
            'organization_id'=>Auth::user()->organization_id,
            'name'=>$r->name, 'location'=>$r->location, 'notes'=>$r->notes
        ]);
        return redirect()->route('rooms.index')->with('ok','Room created');
    }
    public function edit($id) {
        $room = Room::where('organization_id', Auth::user()->organization_id)->findOrFail($id);
        return view('dashboard.rooms.edit', compact('room'));
    }
    public function update(Request $r, $id) {
        $r->validate(['name'=>'required|string|max:255','location'=>'nullable|string','notes'=>'nullable|string']);
        $room = Room::where('organization_id', Auth::user()->organization_id)->findOrFail($id);
        $room->update($r->only('name','location','notes'));
        return redirect()->route('rooms.index')->with('ok','Room updated');
    }
    public function destroy($id) {
        $room = Room::where('organization_id', Auth::user()->organization_id)->findOrFail($id);
        $room->delete();
        return redirect()->route('rooms.index')->with('ok','Room deleted');
    }
}
