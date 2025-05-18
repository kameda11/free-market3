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
            ? Exhibition::join('favorites', 'exhibitions.id', '=', 'favorites.exhibition_id')
            ->where('favorites.user_id', auth()->id())
            ->where('exhibitions.name', 'LIKE', "%{$query}%")
            ->select('exhibitions.*')
            ->with(['purchase', 'favorites', 'comments.user.profile'])
            ->get()
            : collect();

        return view('index', compact('allExhibitions', 'favoriteExhibitions', 'query', 'activeTab'));
    }

    public function storeTab(Request $request)
    {
        session(['active_tab' => $request->input('tab')]);
        return response()->json(['success' => true]);
    }
}
