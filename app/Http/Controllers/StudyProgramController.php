<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudyProgramResource;
use App\Models\StudyProgram;

class StudyProgramController extends Controller
{
    function index()
    {
        $programs = StudyProgram::orderBy('name')->get();
        return StudyProgramResource::collection($programs);
    }

}
