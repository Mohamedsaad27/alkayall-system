<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Product;
use App\Models\Category;
use App\Models\SiteSlider;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;

class HomeController extends Controller
{
    public function index()
    {

        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        $sliders = SiteSlider::all();
        $latestProducts = Product::latest()->take(5)->get();
        $products = Product::where('is_published', true)->get();
        $brands  = Brand::all();
        $branch = auth('contact')->check() ? auth('contact')->user()->getBranch() : null;

        $setting = SiteSetting::get()->first();

        return view('Frontend.home.index', compact('categories', 'latestProducts','branch', 'products','setting','brands','sliders'));
    }

    public function getProductsByCategory($id, Request $request)
    {
        $setting = SiteSetting::get()->first();

        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        $branch = auth('contact')->check() ? auth('contact')->user()->getBranch() : null;
        $brands  = Brand::all();
        $category = Category::find($id);
        if ($category) {
            $products = Product::where('is_published', true)->where('main_category_id', $id)->orWhere('category_id', $id)->get();
            return view('Frontend.category.index', compact('categories', 'products', 'category','setting','brands','branch'));
        }

        return abort(404);
    }

    public function getProductsByBrand($id, Request $request)
    {
        $setting = SiteSetting::get()->first();

        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        $sliders = SiteSlider::all();
        $brands  = Brand::all();
        $branch = auth('contact')->check() ? auth('contact')->user()->getBranch() : null;

        $category = Brand::find($id);
        if ($category) {
            $products = Product::where('is_published', true)->where('brand_id', $id)->get();
            return view('Frontend.category.index', compact('categories', 'products', 'category','setting','sliders','brands','branch'));
        }

        return abort(404);
    }


    public function showProduct($id){
        $setting = SiteSetting::get()->first();

        $product = Product::findOrFail($id);
        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        $sliders = SiteSlider::all();
        $item = Cart::get($id);
        $brands  = Brand::all();

        $branch = auth('contact')->check() ? auth('contact')->user()->getBranch() : null;
        $isOutOfStock = $branch ? $product->getStockByBranch($branch->id) <= $product->min_sele : true;

        return view('Frontend.category.show-product', compact('product','categories','item','setting','sliders','isOutOfStock','brands','branch'));
        
    }

    public function search(Request $request){
        $query = $request->input('query');
        $setting = SiteSetting::get()->first();

        $products = Product::where('is_published', true)->where('name', 'like', "%$query%")->orWhere('description', 'like', "%$query%") ->get();
        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        $brands  = Brand::all();

        $branch = auth('contact')->check() ? auth('contact')->user()->getBranch() : null;

        return view('Frontend.category.show-results', compact('products','categories','setting','branch','brands','query'));
        
    }

    

  
}
