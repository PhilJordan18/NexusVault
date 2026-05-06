<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SearchController extends Controller
{
    public function search(Request $request): View
    {
        $query = trim($request->get('q', ''));
        $results = collect();

        if (strlen($query) >= 1) {
            $results = Service::where('user_id', auth()->id())
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('url', 'like', "%{$query}%")
                        ->orWhere('username', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->select('id', 'name', 'url', 'favicon', 'username')
                ->limit(20)
                ->get();
        }

        return view('components.search-results', compact('results', 'query'));
    }
}
