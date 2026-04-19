<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->grades()->with('evaluation.course')->get()
        );
    }
}
