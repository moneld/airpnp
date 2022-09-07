<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    //


    public function index() : \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $offices = Office::query()
            ->where('approval_status',Office::APPROVAL_APPROVED)
            ->where('hidden',false)
            ->when(request('host_id'), fn($builder) => $builder->whereUserId(request('host_id')))
            ->when(request('user_id'), fn($builder) => $builder->whereRelation('reservations', 'user_id','=',request('user_id')))
            ->when(
                request('lat') && request('lng'),
                fn($builder) => $builder->nearestTo(request('lat'),request('lng')),
                fn($builder) => $builder->orderBy('id','ASC')
            )
            ->with(['images','tags','user'])
            ->withCount(['reservations' => fn($builder) => $builder->where('status',Reservation::STATUS_ACTIVE)])
            ->paginate(20);

        return OfficeResource::collection(
            $offices
        );
    }
}
