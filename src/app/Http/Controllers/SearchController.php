<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        $activeTab = session('active_tab', 'all');

        $allExhibitions = Exhibition::where('name', 'LIKE', "%{$query}%")->get();
        $favoriteExhibitions = auth()->check()
            ? auth()->user()->favorites()->whereHas('exhibition', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })->get()
            : collect();

        return view('index', compact('allExhibitions', 'favoriteExhibitions', 'query', 'activeTab'));
    }

    public function storeTab(Request $request)
    {
        session(['active_tab' => $request->input('tab')]);
        return response()->json(['success' => true]);
    }
}
