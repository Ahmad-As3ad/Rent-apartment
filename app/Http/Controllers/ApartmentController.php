<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{

    public function index(Request $request)
    {
        try {
            $apartments = Apartment::available()
                ->with(['primaryImage', 'owner:id,first_name,last_name'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $apartments->getCollection()->transform(function ($apartment) {
                return $this->transformApartment($apartment);
            });

            return response()->json([
                'success' => true,
                'message' => 'Apartments retrieved successfully',
                'data' => $apartments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve apartments'
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


    private function transformApartment($apartment, $fullDetails = false)
    {
        $data = [
            'id' => $apartment->id,
            'title' => $apartment->title,
            'description' => $apartment->description,
            'address' => $apartment->address,
            'city' => $apartment->city,
            'region' => $apartment->region,
            'price_per_night' => (float) $apartment->price_per_night,
            'number_of_rooms' => $apartment->number_of_rooms,
            'number_of_bathrooms' => $apartment->number_of_bathrooms,
            'max_guests' => $apartment->max_guests,
            'is_available' => (bool) $apartment->is_available,
            'created_at' => $apartment->created_at->format('Y-m-d H:i:s')
        ];

        if ($apartment->primaryImage) {
            $data['primary_image'] = $apartment->primaryImage->image_url;
        }

        if ($apartment->owner) {
            $data['owner'] = [
                'name' => $apartment->owner->first_name . ' ' . $apartment->owner->last_name
            ];
        }

        if ($fullDetails && $apartment->images) {
            $data['images'] = $apartment->images->map(function ($image) {
                return [
                    'url' => $image->image_url,
                    'is_primary' => (bool) $image->is_primary
                ];
            });

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
