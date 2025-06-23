<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_image' => $this->profile_image,
            'type' => $this->type,
            'has_fcm_token' => $this->hasFcmToken(),
            'last_login_at' => $this->last_login_at,
        ];

        // Add customer data if exists
        if ($this->customer) {
            // Calculate the new discount claim variables
            $total_discount_claim = 5;
            $total_current_discount_claim = $this->customer->total_premium_transactions % $total_discount_claim;

            $customerData = [
                'id' => $this->customer->id,
                'user_id' => $this->customer->user_id,
                'address' => $this->customer->address,
                'membership_type_id' => $this->customer->membership_type_id,
                'membership_status' => $this->customer->membership_status,
                'membership_expires_at' => $this->customer->membership_expires_at,
                'is_active' => $this->customer->is_active,
                'total_transactions' => $this->customer->total_transactions,
                'total_premium_transactions' => $this->customer->total_premium_transactions,
                'total_discount_approvals' => $this->customer->total_discount_approvals,
                'total_discount_claims' => $total_discount_claim,
                'total_current_discount_claims' => $total_current_discount_claim,
                'created_at' => $this->customer->created_at,
                'updated_at' => $this->customer->updated_at,
            ];

            // Include membership type data directly in customer if it exists
            if ($this->customer->membershipType) {
                $customerData['membership_type'] = [
                    'id' => $this->customer->membershipType->id,
                    'name' => $this->customer->membershipType->name,
                    'benefits' => $this->customer->membershipType->benefits,
                    'is_active' => $this->customer->membershipType->is_active,
                    'created_at' => $this->customer->membershipType->created_at,
                    'updated_at' => $this->customer->membershipType->updated_at,
                ];
            }

            $data['customer'] = $customerData;
        }

        // Add staff data if exists
        if ($this->staff) {
            $data['staff'] = $this->staff;
        }

        return $data;
    }
}
