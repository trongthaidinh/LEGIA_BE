<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\ZhPage;
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

    public function zhIndex()
    {
        try {
            $zhPages = ZhPage::all();
            return responseJson($zhPages, 200, 'Zh Pages retrieved successfully');
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

    public function zhShow($id)
    {
        try {
            $zhPage = ZhPage::find($id);

            if (!$zhPage) {
                return responseJson(null, 404, 'Zh Page not found');
            }

            return responseJson($zhPage, 200, 'Zh Page found');
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

    public function zhStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $validated['slug'] = $validated['name'];

            $zhPage = ZhPage::create($validated);

            return responseJson($zhPage, 201, 'Zh Page created successfully');
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

    public function zhUpdate(Request $request, $slug)
    {
        try {
            $validated = $request->validate([
                'name'    => 'sometimes|required|string|max:255',
                'content' => 'nullable|string',
            ]);

            $zhPage = ZhPage::where('slug', $slug)->first();

            if (!$zhPage) {
                return responseJson(null, 404, 'Page not found');
            }

            if (isset($validated['name'])) {
                $validated['slug'] = $validated['name'];
            }

            $zhPage->update($validated);

            return responseJson($zhPage, 200, 'Zh Page updated successfully');
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

    public function zhDestroy($id)
    {
        try {
            $zhPage = ZhPage::find($id);

            if (!$zhPage) {
                return responseJson(null, 404, 'Page not found');
            }

            $zhPage->delete();

            return responseJson(null, 200, 'Zh Page deleted successfully');
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

    public function zhGetPageBySlug($slug)
    {
        try {
            $zhPage = ZhPage::where('slug', $slug)->first();

            if (!$zhPage) {
                return responseJson(null, 404, 'Page not found');
            }

            return responseJson($zhPage, 200, 'Zh Page found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
