<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = Apartment::available()
                ->with(['primaryImage', 'owner:id,first_name,last_name']);


            if ($request->has('city') && $request->city != '') {
                $query->byCity($request->city);
            }

            if ($request->has('rooms') && $request->rooms > 0) {
                $query->byRooms($request->rooms);
            }

            if ($request->has('min_price') || $request->has('max_price')) {
                $minPrice = $request->min_price ?? 0;
                $maxPrice = $request->max_price ?? 0;
                $query->priceRange($minPrice, $maxPrice);
            }


            if ($request->has('sort_by')) {
                switch ($request->sort_by) {
                    case 'highest_price':
                        $query->orderByHighestPrice();
                        break;

                    case 'lowest_price':
                        $query->orderByLowestPrice();
                        break;

                    case 'newest':
                    default:
                        $query->orderBy('created_at', 'desc');
                        break;
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = $request->input('per_page', 10);
            $apartments = $query->paginate($perPage);

            $apartments->getCollection()->transform(function ($apartment) {
                return $this->transformApartment($apartment);
            });

            $appliedFilters = [
                'city' => $request->city ?? null,
                'rooms' => $request->rooms ?? null,
                'min_price' => $request->min_price ?? null,
                'max_price' => $request->max_price ?? null,
                'sort_by' => $request->sort_by ?? 'newest'
            ];

            return response()->json([
                'success' => true,
                'message' => 'The apartments were successfully purchased',
                'data' => $apartments,
                'filters' => $appliedFilters,
                'meta' => [
                    'total' => $apartments->total(),
                    'current_page' => $apartments->currentPage(),
                    'last_page' => $apartments->lastPage(),
                    'per_page' => $apartments->perPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' =>'Failed to bring apartments'
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $apartment = Apartment::with(['images', 'owner:id,first_name,last_name,phone_number'])
                ->available()
                ->find($id);

            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'The apartment does not exist'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Apartment data has been successfully retrieved',
                'data' => $this->transformApartment($apartment, true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve apartment data'
            ], 500);
        }
    }

    public function getCities()
    {
        try {
            $cities = Apartment::available()
                ->distinct('city')
                ->orderBy('city')
                ->pluck('city')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Cities have been successfully brought in',
                'data' => $cities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bring cities'
            ], 500);
        }
    }

    public function getPriceRange()
    {
        try {
            $minPrice = Apartment::available()->min('price_per_night');
            $maxPrice = Apartment::available()->max('price_per_night');

            return response()->json([
                'success' => true,
                'message' => 'Price range fetched successfully',
                'data' => [
                    'min' => (float) ($minPrice ?? 0),
                    'max' => (float) ($maxPrice ?? 0)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bring the price range'
            ], 500);
        }
    }

    public function getRoomsOptions()
    {
        try {
            $roomsOptions = Apartment::available()
                ->distinct('number_of_rooms')
                ->orderBy('number_of_rooms')
                ->pluck('number_of_rooms')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Room options have been retrieved successfully',
                'data' => $roomsOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bring room options'
            ], 500);
        }
    }


    private function transformApartment($apartment, $fullDetails = false)
    {
        $data = [
            'id' => $apartment->id,
            'title' => $apartment->title,
            'description' => $apartment->description,
            'address' => $apartment->address,
            'city' => $apartment->city,
            'region' => $apartment->region,
            'full_address' => $apartment->full_address,
            'price_per_night' => (float) $apartment->price_per_night,
            'formatted_price' => $apartment->formatted_price,
            'number_of_rooms' => $apartment->number_of_rooms,
            'area' => $apartment->area,
            'is_available' => (bool) $apartment->is_available,
            'created_at' => $apartment->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $apartment->updated_at->format('Y-m-d H:i:s'),
            'owner' => $apartment->owner ? [
                'id' => $apartment->owner->id,
                'name' => $apartment->owner->first_name . ' ' . $apartment->owner->last_name
            ] : null
        ];

        if ($apartment->primaryImage) {
            $data['primary_image'] = $apartment->primaryImage->image_url;
        }

        if ($fullDetails && $apartment->images) {
            $data['images'] = $apartment->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'is_primary' => (bool) $image->is_primary
                ];
            });

            if ($apartment->latitude && $apartment->longitude) {
                $data['location'] = [
                    'latitude' => (float) $apartment->latitude,
                    'longitude' => (float) $apartment->longitude
                ];
            }

            $data['amenities'] = [
                'has_kitchen' => (bool) $apartment->has_kitchen,
                'has_air_conditioning' => (bool) $apartment->has_air_conditioning,
                'has_wifi' => (bool) $apartment->has_wifi,
                'has_parking' => (bool) $apartment->has_parking
            ];
        }

        return $data;
    }
}
