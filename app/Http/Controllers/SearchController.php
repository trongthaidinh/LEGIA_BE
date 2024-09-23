<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use App\Models\Experience;
use App\Models\News;
use Illuminate\Http\Request;
use Exception;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $limit = $request->input('limit', 6);
            $page = $request->input('page', 1);

            if (!$query) {
                return responseJson(null, 400, 'Search query is required');
            }

            $products = Product::where('name', 'LIKE', "%{$query}%")
                ->orWhere('summary', 'LIKE', "%{$query}%")
                ->paginate($limit, ['*'], 'products_page', $page);

            $services = Service::where('name', 'LIKE', "%{$query}%")
                ->orWhere('summary', 'LIKE', "%{$query}%")
                ->paginate($limit, ['*'], 'services_page', $page);

            $experiences = Experience::where('name', 'LIKE', "%{$query}%")
                ->orWhere('summary', 'LIKE', "%{$query}%")
                ->paginate($limit, ['*'], 'experiences_page', $page);

            $news = News::where('title', 'LIKE', "%{$query}%")
                ->orWhere('content', 'LIKE', "%{$query}%")
                ->paginate($limit, ['*'], 'news_page', $page);

            $results = [
                'products' => $products,
                'services' => $services,
                'experiences' => $experiences,
                'news' => $news,
            ];

            return responseJson($results, 200, 'Search results retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
