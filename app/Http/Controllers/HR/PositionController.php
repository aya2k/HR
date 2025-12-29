<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;
use App\Traits\ApiResponder;
use App\Http\Requests\Position\PositionRequest;
use App\Http\Resources\Position\PositionResource;

class PositionController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $positions = Position::with('branch')->latest()->get();
        return PositionResource::collection($positions);
    }



    public function store(PositionRequest $request)
    {
        $position = Position::create($request->validated());
        return new PositionResource($position);
    }


    public function show(Position $position)
    {
        return new PositionResource($position->load('branch'));
    }


    public function update(PositionRequest $request, Position $position)
    {
        $position->update($request->validated());
        return new PositionResource($position);
    }


    public function destroy(Position $position)
    {
        $position->delete();
        return response()->json(['message' => 'Position deleted successfully']);
    }
}
