<?php
 
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Http\Resources\CouponResource;
use App\Models\RideRequest;
use App\Models\Service;
use Illuminate\Support\Str;
 
class CouponController extends Controller
{
    public function getList(Request $request)
    {
        try {
            $user = auth()->user();
            
            // 1. Pre-calculate user eligibility stats
            // We do this once to avoid running these counts inside the query loop
            $totalRides = RideRequest::where('rider_id', $user->id)->count();
            $completedRides = RideRequest::where('rider_id', $user->id)
                ->where('status', 'completed')
                ->count();
 
            // Get count of usage per coupon code for this specific rider
            $userCouponUsage = RideRequest::where('rider_id', $user->id)
                ->where('status', 'completed')
                ->whereNotNull('coupon_code')
                ->selectRaw('coupon_code, count(*) as times_used')
                ->groupBy('coupon_code')
                ->pluck('times_used', 'coupon_code')
                ->toArray();
 
            // 2. Base Query (Global Constraints)
            $query = Coupon::where('status', 1)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now());
 
            // 3. Usage Limit Logic (Crucial Fix)
            // We filter out coupons where the user has already hit the 'usage_limit_per_rider'
            $query->where(function ($q) use ($userCouponUsage) {
                foreach ($userCouponUsage as $code => $count) {
                    // If a coupon was used, ensure its limit is strictly greater than the usage count
                    $q->where(function($sq) use ($code, $count) {
                        $sq->where('code', '!=', $code)
                        ->orWhere('usage_limit_per_rider', '>', $count);
                    });
                }
            });
 
            // 4. Eligibility Logic Group
            $query->where(function ($q) use ($request, $totalRides, $completedRides) {
                
                // TYPE: Service Wise & Region Wise
                if ($request->filled('service_id')) {
                    $service = Service::find($request->service_id);
                    
                    $q->orWhere(function($sq) use ($request) {
                        $sq->where('coupon_type', 'service_wise')
                        ->whereJsonContains('service_ids', ["$request->service_id"]);
                    });
 
                    if ($service && $service->region_id) {
                        $q->orWhere(function($sq) use ($service) {
                            $sq->where('coupon_type', 'region_wise')
                            ->whereJsonContains('region_ids', ["$service->region_id"]);
                        });
                    }
                }
 
                // TYPE: First Ride
                if ($completedRides === 0) {
                    $q->orWhere('coupon_type', 'first_ride');
                }
 
                // TYPE: New User
                if ($totalRides === 0) {
                    $q->orWhere('coupon_type', 'new_user');
                }
                
                // Include 'all' type if applicable
                $q->orWhere('coupon_type', 'all');
            });
 
            // 5. Search by Code
            $query->when($request->code, function ($q) use ($request) {
                return $q->where('code', 'LIKE', '%' . $request->code . '%');
            });
 
            // 6. Pagination
            $per_page = $request->input('per_page', config('constant.PER_PAGE_LIMIT', 15));
            if ($per_page == -1) {
                $per_page = $query->count() ?: 1;
            }
 
            $coupons = $query->orderBy('title', 'asc')->paginate($per_page);
            $items = CouponResource::collection($coupons);
 
            return json_custom_response([
                'pagination' => json_pagination_response($items),
                'data' => $items,
            ]);
 
        } catch (\Throwable $th) {
            return json_custom_response($th->getMessage(), 500);
        }
    }
}
 