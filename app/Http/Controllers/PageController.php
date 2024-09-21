<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Exception;

class PageController extends Controller
{

    public function index()
    {
        try {
            $pages = Page::all();
            return responseJson($pages, 200, 'Pages retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $page = Page::find($id);

            if (!$page) {
                return responseJson(null, 404, 'Page not found');
            }

            return responseJson($page, 200, 'Page found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $page = Page::create($validated);

            return responseJson($page, 201, 'Page created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $slug)
    {
        try {
            $validated = $request->validate([
                'name'    => 'sometimes|required|string|max:255',
                'content' => 'nullable|string',
            ]);

            $page = Page::where('slug', $slug)->first();

            if (!$page) {
                return responseJson(null, 404, 'Page not found');
            }

            $page->update($validated);

            return responseJson($page, 200, 'Page updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }



    public function destroy($id)
    {
        try {
            $page = Page::find($id);

            if (!$page) {
                return responseJson(null, 404, 'Page not found');
            }

            $page->delete();

            return responseJson(null, 200, 'Page deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function getPageBySlug($slug)
    {
        try {
            $page = Page::where('slug', $slug)->first();

            if (!$page) {
                return responseJson(null, 404, 'Page not found');
            }

            return responseJson($page, 200, 'Page found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
