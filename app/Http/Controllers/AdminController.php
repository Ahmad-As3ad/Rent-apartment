<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{

    public function __construct()
    {
    }


   /**
 * عرض قائمة المستخدمين للمراجعة
 */
public function getUsersForReview(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);

        $query = User::query();

        // فلترة حسب النوع
        if ($request->has('user_type') && $request->user_type != '') {
            $query->where('user_type', $request->user_type);
        }

        // فلترة حسب الحالة
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
                'date_of_birth' => $user->date_of_birth,
                'user_type' => $user->user_type,
                'status' => $user->status,
                'profile_completed_at' => $user->profile_completed_at,
                'is_profile_complete' => $user->isProfileComplete(),
                'is_approved' => $user->status === 'approved',
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'reviewed_at' => $user->reviewed_at ? $user->reviewed_at->format('Y-m-d H:i:s') : null,
                'admin_notes' => $user->admin_notes
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users,
            'meta' => [
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve users'
        ], 500);
    }
}
    public function getUserDetails($id)
    {
        try {
            $user = User::with(['reviewer', 'apartments'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User details retrieved successfully',
                'data' => $this->transformUserForAdmin($user, true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details'
            ], 500);
        }
    }


    public function approveUser(Request $request, $id)
    {
        try {
            $admin = $request->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already approved'
                ], 400);
            }

            $validated = $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            $user->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'admin_notes' => $validated['notes'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User approved successfully',
                'data' => $this->transformUserForAdmin($user)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve user'
            ], 500);
        }
    }


    public function rejectUser(Request $request, $id)
    {
        try {
            $admin = $request->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already rejected'
                ], 400);
            }

            $validated = $request->validate([
                'notes' => 'required|string|max:500',
                'reason' => 'required|string|max:255'
            ]);

            $user->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'admin_notes' => $validated['notes'] . ' | Reason: ' . $validated['reason']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User rejected successfully',
                'data' => $this->transformUserForAdmin($user)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user'
            ], 500);
        }
    }


    public function suspendUser(Request $request, $id)
    {
        try {
            $admin = $request->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validated = $request->validate([
                'notes' => 'required|string|max:500',
                'reason' => 'required|string|max:255',
                'suspension_days' => 'nullable|integer|min:1|max:365'
            ]);

            $user->update([
                'status' => 'suspended',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'admin_notes' => $validated['notes'] . ' | Reason: ' . $validated['reason'] .
                               ' | Suspension days: ' . ($validated['suspension_days'] ?? 'indefinite')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully',
                'data' => $this->transformUserForAdmin($user)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user'
            ], 500);
        }
    }

    public function manageApartments(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            $query = Apartment::with(['owner', 'primaryImage', 'images'])
                ->where('approved_by_admin', false)
                ->latest();

            $apartments = $query->paginate($perPage);

            $apartments->getCollection()->transform(function ($apartment) {
                return $this->transformApartmentForAdmin($apartment);
            });

            return response()->json([
                'success' => true,
                'message' => 'Apartments pending approval retrieved successfully',
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
                'message' => 'Failed to retrieve apartments'
            ], 500);
        }
    }


    public function approveApartment(Request $request, $id)
    {
        try {
            $admin = $request->user();
            $apartment = Apartment::find($id);

            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apartment not found'
                ], 404);
            }

            $validated = $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            $apartment->update([
                'approved_by_admin' => true,
                'is_available' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Apartment approved successfully',
                'data' => $this->transformApartmentForAdmin($apartment)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve apartment'
            ], 500);
        }
    }


    public function rejectApartment(Request $request, $id)
    {
        try {
            $admin = $request->user();
            $apartment = Apartment::find($id);

            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apartment not found'
                ], 404);
            }

            $validated = $request->validate([
                'notes' => 'required|string|max:500',
                'reason' => 'required|string|max:255'
            ]);

            $apartment->update([
                'approved_by_admin' => false,
                'is_available' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Apartment rejected successfully',
                'data' => $this->transformApartmentForAdmin($apartment)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject apartment'
            ], 500);
        }
    }

    private function transformUserForAdmin($user, $fullDetails = false)
    {
        $data = [
            'id' => $user->id,
            'phone_number' => $user->phone_number,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'profile_picture' => $user->profile_picture_url,
            'date_of_birth' => $user->date_of_birth,
            'id_card_picture' => $user->id_card_picture_url,
            'user_type' => $user->user_type,
            'status' => $user->status,
            'profile_completed_at' => $user->profile_completed_at,
            'is_profile_complete' => $user->isProfileComplete(),
            'is_approved' => $user->isApproved(),
            'is_pending' => $user->isPending(),
            'is_rejected' => $user->isRejected(),
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'reviewed_at' => $user->reviewed_at?->format('Y-m-d H:i:s'),
            'admin_notes' => $user->admin_notes
        ];

        if ($user->reviewer) {
            $data['reviewed_by'] = [
                'id' => $user->reviewer->id,
                'name' => $user->reviewer->full_name
            ];
        }

        if ($fullDetails) {
            $data['apartments_count'] = $user->apartments()->count();
            $data['phone_verified_at'] = $user->phone_verified_at?->format('Y-m-d H:i:s');
        }

        return $data;
    }


    private function transformApartmentForAdmin($apartment)
    {
        return [
            'id' => $apartment->id,
            'title' => $apartment->title,
            'description' => $apartment->description,
            'address' => $apartment->address,
            'city' => $apartment->city,
            'region' => $apartment->region,
            'price_per_night' => (float) $apartment->price_per_night,
            'number_of_rooms' => $apartment->number_of_rooms,
            'number_of_bathrooms' => $apartment->number_of_bathrooms,
            'area' => $apartment->area,
            'is_available' => (bool) $apartment->is_available,
            'approved_by_admin' => (bool) $apartment->approved_by_admin,
            'created_at' => $apartment->created_at->format('Y-m-d H:i:s'),
            'owner' => $apartment->owner ? [
                'id' => $apartment->owner->id,
                'name' => $apartment->owner->full_name,
                'phone' => $apartment->owner->phone_number
            ] : null,
            'images_count' => $apartment->images->count(),
            'primary_image' => $apartment->primaryImage?->image_url
        ];
    }
}
