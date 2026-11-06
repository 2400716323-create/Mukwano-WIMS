<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('administration.roles.index', [
            'roles' => config('roles.roles'),
        ]);
    }
}
