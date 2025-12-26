<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                'message' => 'Apartments retrieved successfully',
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
                'message' => 'Failed to retrieve apartments'
            ], 500);
        }
    }

    public function myApartments(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Apartment::where('owner_id', $user->id)
                ->with(['primaryImage', 'images'])
                ->latest();

            $perPage = $request->input('per_page', 10);
            $apartments = $query->paginate($perPage);

            $apartments->getCollection()->transform(function ($apartment) {
                return $this->transformApartment($apartment, true);
            });

            return response()->json([
                'success' => true,
                'message' => 'Your apartments retrieved successfully',
                'data' => $apartments,
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
                'message' => 'Failed to retrieve your apartments'
            ], 500);
        }
    }

/**
 * Create a new apartment
 */
public function store(Request $request)
{
    try {
        // Get authenticated user
        $user = Auth::user();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Check if user is an owner
        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only property owners can create apartments'
            ], 403);
        }
if (!$user->canAddApartments()) {
    return response()->json([
        'success' => false,
        'message' => 'Your account must be approved by administration to add apartments'
    ], 403);
}
        // Check if user profile is complete
        if (!$user->isProfileComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your profile before adding apartments',
                'profile_incomplete' => true
            ], 403);
        }

        // Validate input data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'region' => 'required|string|max:100',
            'price_per_night' => 'required|numeric|min:0|max:999999.99',
            'number_of_rooms' => 'required|integer|min:1|max:20',
            'number_of_bathrooms' => 'required|integer|min:1|max:10', // إضافة عدد الحمامات
            'area' => 'required|numeric|min:10|max:10000' // إضافة المساحة
        ]);

        // Create apartment with owner_id from authenticated user
        $apartment = Apartment::create([
            'owner_id' => $user->id,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'address' => $validatedData['address'],
            'city' => $validatedData['city'],
            'region' => $validatedData['region'],
            'price_per_night' => $validatedData['price_per_night'],
            'number_of_rooms' => $validatedData['number_of_rooms'],
            'number_of_bathrooms' => $validatedData['number_of_bathrooms'], // حفظ عدد الحمامات
            'area' => $validatedData['area'], // حفظ المساحة
            'is_available' => true,
            'approved_by_admin' => false
        ]);

        // Load relationships
        $apartment->load(['images', 'owner']);

        return response()->json([
            'success' => true,
            'message' => 'Apartment created successfully and awaiting admin approval',
            'data' => $this->transformApartment($apartment, true)
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create apartment'
        ], 500);
    }
}
  /**
 * Update an apartment
 */
public function update(Request $request, $id)
{
    try {
        $user = Auth::user();

        // Find the apartment
        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json([
                'success' => false,
                'message' => 'Apartment not found'
            ], 404);
        }

        // Check if user is authorized to update this apartment
        if (!$apartment->isOwnedBy($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this apartment'
            ], 403);
        }

        // Validate input data
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|min:50',
            'address' => 'sometimes|required|string|max:500',
            'city' => 'sometimes|required|string|max:100',
            'region' => 'sometimes|required|string|max:100',
            'price_per_night' => 'sometimes|required|numeric|min:0|max:999999.99',
            'number_of_rooms' => 'sometimes|required|integer|min:1|max:20',
            'number_of_bathrooms' => 'sometimes|required|integer|min:1|max:10', // إضافة
            'area' => 'sometimes|required|numeric|min:10|max:10000', // إضافة
            'is_available' => 'sometimes|boolean'
        ]);

        // Update apartment data
        $apartment->update($validatedData);

        // Reset admin approval if data was changed
        if ($apartment->wasChanged() && $apartment->approved_by_admin) {
            $apartment->update(['approved_by_admin' => false]);
        }

        // Load relationships
        $apartment->load(['images', 'owner']);

        return response()->json([
            'success' => true,
            'message' => 'Apartment updated successfully',
            'data' => $this->transformApartment($apartment, true)
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update apartment'
        ], 500);
    }
}
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $apartment = Apartment::find($id);

            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apartment not found'
                ], 404);
            }

            if (!$user || $apartment->owner_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this apartment'
                ], 403);
            }

            $apartment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Apartment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete apartment'
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
                'message' => 'Cities retrieved successfully',
                'data' => $cities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cities'
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
                'message' => 'Failed to retrieve price range'
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
                'message' => 'Room options retrieved successfully',
                'data' => $roomsOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve room options'
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
        'number_of_bathrooms' => $apartment->number_of_bathrooms, // إضافة
        'area' => $apartment->area, // إضافة
        'is_available' => (bool) $apartment->is_available,
        'approved_by_admin' => isset($apartment->approved_by_admin) ? (bool) $apartment->approved_by_admin : null,
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
    }

    return $data;
}}
