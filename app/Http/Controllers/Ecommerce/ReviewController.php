<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Review;
use Illuminate\Http\Request;

/** Product reviews moderation. */
class ReviewController extends EcommerceController
{
    public function index(Request $request)
    {
        $this->need('ecommerce.manage_reviews');
        $reviews = Review::query()->with('product')
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest('id')->paginate(25)->withQueryString();

        return view('ecommerce.reviews.index', [
            'reviews' => $reviews,
            'pendingCount' => Review::where('is_approved', false)->count(),
        ]);
    }

    public function approve(Review $review)
    {
        $this->need('ecommerce.manage_reviews');
        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('success', $review->is_approved ? 'Review approved.' : 'Review hidden.');
    }

    public function destroy(Review $review)
    {
        $this->need('ecommerce.manage_reviews');
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
