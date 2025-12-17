<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApartmentController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $query = Apartment::available()
                ->with(['primaryImage', 'owner:id,first_name,last_name,phone_number']);

            $query = $this->applyFilters($query, $request);

            $query->orderBy('created_at', 'desc');

            $perPage = $request->input('per_page', 10);
            $apartments = $query->paginate($perPage);

            $apartments->getCollection()->transform(function ($apartment) {
                return $this->transformApartment($apartment);
            });

            return response()->json([
                'success' => true,
                'message' => 'Apartments retrieved successfully',
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
                'message' => 'Failed to retrieve apartments',
                'error' => $e->getMessage()
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
                    'message' => 'Apartment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Apartment retrieved successfully',
                'data' => $this->transformApartment($apartment, true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve apartment'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'region' => 'required|string|max:100',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'price_per_night' => 'required|numeric|min:1',
                'number_of_rooms' => 'required|integer|min:1',
                'number_of_bathrooms' => 'required|integer|min:1',
                'max_guests' => 'required|integer|min:1',
                'area' => 'nullable|integer|min:1',
                'has_kitchen' => 'boolean',
                'has_air_conditioning' => 'boolean',
                'has_wifi' => 'boolean',
                'has_parking' => 'boolean',
                'has_washer' => 'boolean',
                'has_tv' => 'boolean',
                'images' => 'required|array|min:1',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();

            if (!$user->isOwner()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only owners can add apartments'
                ], 403);
            }
            $apartment = Apartment::create([
                'owner_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'region' => $validated['region'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'price_per_night' => $validated['price_per_night'],
                'number_of_rooms' => $validated['number_of_rooms'],
                'number_of_bathrooms' => $validated['number_of_bathrooms'],
                'max_guests' => $validated['max_guests'],
                'area' => $validated['area'] ?? null,
                'has_kitchen' => $validated['has_kitchen'] ?? false,
                'has_air_conditioning' => $validated['has_air_conditioning'] ?? false,
                'has_wifi' => $validated['has_wifi'] ?? false,
                'has_parking' => $validated['has_parking'] ?? false,
                'has_washer' => $validated['has_washer'] ?? false,
                'has_tv' => $validated['has_tv'] ?? false,
                'approved_by_admin' => false
            ]);

            if ($request->hasFile('images')) {
                $this->uploadApartmentImages($apartment, $request->file('images'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Apartment created successfully. Waiting for admin approval.',
                'data' => $this->transformApartment($apartment)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $apartment = Apartment::find($id);

            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apartment not found'
                ], 404);
            }

            if (!$apartment->isOwnedBy($request->user()->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this apartment'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'address' => 'sometimes|string|max:500',
                'city' => 'sometimes|string|max:100',
                'region' => 'sometimes|string|max:100',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'price_per_night' => 'sometimes|numeric|min:1',
                'number_of_rooms' => 'sometimes|integer|min:1',
                'number_of_bathrooms' => 'sometimes|integer|min:1',
                'max_guests' => 'sometimes|integer|min:1',
                'area' => 'nullable|integer|min:1',
                'has_kitchen' => 'boolean',
                'has_air_conditioning' => 'boolean',
                'has_wifi' => 'boolean',
                'has_parking' => 'boolean',
                'has_washer' => 'boolean',
                'has_tv' => 'boolean',
                'is_available' => 'boolean',
                'images' => 'sometimes|array',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $apartment->update($validated);

            if ($request->hasFile('images')) {
                $this->uploadApartmentImages($apartment, $request->file('images'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Apartment updated successfully',
                'data' => $this->transformApartment($apartment)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update apartment',
                'error' => $e->getMessage()
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

    public function getRegions()
    {
        try {
            $regions = Apartment::available()
                ->distinct('region')
                ->orderBy('region')
                ->pluck('region')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Regions retrieved successfully',
                'data' => $regions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve regions'
            ], 500);
        }
    }


    public function getFilterOptions()
    {
        try {
            $minPrice = Apartment::available()->min('price_per_night');
            $maxPrice = Apartment::available()->max('price_per_night');
            $maxRooms = Apartment::available()->max('number_of_rooms');
            $maxGuests = Apartment::available()->max('max_guests');

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'price_range' => [
                        'min' => (float) ($minPrice ?? 0),
                        'max' => (float) ($maxPrice ?? 0)
                    ],
                    'rooms_range' => [
                        'min' => 1,
                        'max' => (int) ($maxRooms ?? 5)
                    ],
                    'guests_range' => [
                        'min' => 1,
                        'max' => (int) ($maxGuests ?? 10)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve filter options'
            ], 500);
        }
    }


    private function applyFilters($query, Request $request)
    {
        if ($request->has('city') && $request->city != '') {
            $query->byCity($request->city);
        }

        if ($request->has('region') && $request->region != '') {
            $query->byRegion($request->region);
        }

        if ($request->has('min_price') || $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        if ($request->has('rooms')) {
            $query->byRooms($request->rooms);
        }

        if ($request->has('guests')) {
            $query->where('max_guests', '>=', $request->guests);
        }

        $amenities = [];
        if ($request->has('has_kitchen') && $request->has_kitchen == '1') $amenities[] = 'kitchen';
        if ($request->has('has_air_conditioning') && $request->has_air_conditioning == '1') $amenities[] = 'air_conditioning';
        if ($request->has('has_wifi') && $request->has_wifi == '1') $amenities[] = 'wifi';
        if ($request->has('has_parking') && $request->has_parking == '1') $amenities[] = 'parking';
        if ($request->has('has_washer') && $request->has_washer == '1') $amenities[] = 'washer';
        if ($request->has('has_tv') && $request->has_tv == '1') $amenities[] = 'tv';

        if (!empty($amenities)) {
            $query->withAmenities($amenities);
        }

        return $query;
    }


    private function uploadApartmentImages($apartment, $images)
    {
        foreach ($images as $index => $image) {
            $path = $image->store('apartments/' . $apartment->id, 'public');

            $isPrimary = $index === 0;

            $apartment->images()->create([
                'image_path' => $path,
                'is_primary' => $isPrimary,
                'order' => $index
            ]);
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
            'number_of_bathrooms' => $apartment->number_of_bathrooms,
            'max_guests' => $apartment->max_guests,
            'area' => $apartment->area,
            'amenities' => [
                'has_kitchen' => (bool) $apartment->has_kitchen,
                'has_air_conditioning' => (bool) $apartment->has_air_conditioning,
                'has_wifi' => (bool) $apartment->has_wifi,
                'has_parking' => (bool) $apartment->has_parking,
                'has_washer' => (bool) $apartment->has_washer,
                'has_tv' => (bool) $apartment->has_tv
            ],
            'is_available' => (bool) $apartment->is_available,
            'approved_by_admin' => (bool) $apartment->approved_by_admin,
            'created_at' => $apartment->created_at,
            'updated_at' => $apartment->updated_at,
            'owner' => $apartment->owner ? [
                'id' => $apartment->owner->id,
                'name' => $apartment->owner->first_name . ' ' . $apartment->owner->last_name,
                'phone' => $apartment->owner->phone_number
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
                    'is_primary' => (bool) $image->is_primary,
                    'order' => $image->order
                ];
            });

            if ($apartment->latitude && $apartment->longitude) {
                $data['location'] = [
                    'latitude' => (float) $apartment->latitude,
                    'longitude' => (float) $apartment->longitude
                ];
            }
        }

        return $data;
    }
}
