<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IndustryController extends Controller
{
    public function index()
    {
        return view('pages.industries.index');
    }

    public function show(string $slug)
    {
        // No industry records exist yet. An unknown slug must 404 rather than
        // return 200 with a stub body.
        throw new NotFoundHttpException;
    }
}
