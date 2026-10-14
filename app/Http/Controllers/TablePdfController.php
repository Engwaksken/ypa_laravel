<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class TablePdfController extends Controller
{
    public static function middleware(): array
    {
        return [new Middleware('auth'), new Middleware('user.status')];
    }

    public function store(Request $request): Response
    {
        abort_if(strlen($request->getContent()) > 1_000_000, 413, 'The table is too large. Apply filters before exporting.');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'headers' => ['required', 'array', 'min:1', 'max:30'],
            'headers.*' => ['required', 'string', 'max:200'],
            'rows' => ['present', 'array', 'max:500'],
            'rows.*' => ['array', 'min:1', 'max:30'],
            'rows.*.*' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach ($data['rows'] as $row) {
            if (count($row) !== count($data['headers'])) {
                throw ValidationException::withMessages(['rows' => 'Each row must match the selected columns.']);
            }
        }

        // Only renders text supplied by the browser. It never queries additional records,
        // accepts template HTML, or expands the page's existing data permissions.
        return Pdf::loadView('documents.table-pdf', $data)
            ->setPaper('a4', count($data['headers']) > 6 ? 'landscape' : 'portrait')
            ->stream('table.pdf');
    }
}
