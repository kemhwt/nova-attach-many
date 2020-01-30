<?php

namespace NovaAttachMany\Http\Controllers;

use Laravel\Nova\Resource;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachController extends Controller
{
    public function create(NovaRequest $request, $parent, $relationship)
    {
        return [
            'available' => $this->getAvailableResources($request, $relationship),
        ];
    }

    public function edit(NovaRequest $request, $parent, $parentId, $relationship)
    {
        $relateds = $request->findResourceOrFail()->model()->{$relationship};
        $quantities = (object) array();
        foreach ($relateds as $item) {
            $id = $item->id;
            $quantities->$id = $item->pivot->quantity;
        }
        return [
            'selected' => $relateds->pluck('id'),
            'available' => $this->getAvailableResources($request, $relationship),
            'quantities' => $quantities
        ];
    }

    public function getAvailableResources($request, $relationship)
    {
        $resourceClass = $request->newResource();

        $field = $resourceClass
            ->availableFields($request)
            ->where('component', 'nova-attach-many')
            ->where('attribute', $relationship)
            ->first();

        $query = $field->resourceClass::newModel();

        return $field->resourceClass::relatableQuery($request, $query)->get()
            ->mapInto($field->resourceClass)
            ->filter(function ($resource) use ($request, $field) {
                return $request->newResource()->authorizedToAttach($request, $resource->resource);
            })->map(function($resource) {
                return [
                    'display' => $resource->title(),
                    'value' => $resource->getKey(),
                ];
            })->sortBy('display')->values();
    }
}