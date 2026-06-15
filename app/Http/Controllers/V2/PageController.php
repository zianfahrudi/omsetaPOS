<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function soon(Request $request): View
    {
        return view('v2.soon', ['module' => $request->string('m')->value() ?: 'Modul']);
    }
}
