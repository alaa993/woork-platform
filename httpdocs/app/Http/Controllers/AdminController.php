<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Gate;
use App\Models\{Organization,User,Plan};
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminController extends Controller {
  public function index(){
    $this->authorize();
    $orgs = Organization::with('plan','subscription')->orderBy('id','desc')->paginate(20);
    return view('admin.index', compact('orgs'));
  }
  public function users(){
    $this->authorize();
    $users = User::orderBy('id','desc')->paginate(20);
    return view('admin.users', compact('users'));
  }
  protected function authorize(){
    if (!auth()->check() || auth()->user()->role !== 'super_admin') {
      throw new HttpException(403, 'Forbidden');
    }
  }
}
