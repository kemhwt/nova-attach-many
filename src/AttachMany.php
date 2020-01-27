<?php

namespace NovaAttachMany;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Authorizable;
use NovaAttachMany\Rules\ArrayRules;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class AttachMany extends Field
{
    use Authorizable;

    public $height = '300px';

    public $fullWidth = false;

    public $showToolbar = true;

    public $showCounts = false;

    public $showPreview = false;

    public $showOnIndex = false;

    public $showOnDetail = false;

    public $extraFields = [];

    public $component = 'nova-attach-many';

    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resource = $resource;

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;

        $this->fillUsing(function($request, $model, $attribute, $requestAttribute) use($resource) {
            if(is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
                $model::saved(function($model) use($attribute, $request) {
                    error_log('LOG_1');
                    error_log($request->$attribute);
                    error_log($attribute);
                    error_log($request->quantities);
//                    $items = json_decode($request->$attribute, true);
//                    $attaches = [];
//                    foreach ($items as $item) {
//                        array_push($attaches, [
//                            'contract_id' => $model->id,
//
//                        ]);
//                    }

                    $quantities = json_decode($request->quantities);
                    $array = json_decode($request->$attribute, true);
                    $processes = [];
                    foreach ($array as $item) {
                        $processes[$item] = ['quantity' => $quantities->$item];
                    }

                    $model->$attribute()->sync(
//                        json_decode($request->$attribute, true)
                        $processes
                    );

//                    $model->$attribute()->saveMany(
//                        json_decode($request->$attribute, true)
//                    );
                });

                unset($request->$attribute);
            }
        });
    }

    public function rules($rules)
    {
        $rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        $this->rules = [ new ArrayRules($rules) ];

        return $this;
    }

    public function resolve($resource, $attribute = null)
    {
        $this->withMeta([
            'height' => $this->height,
            'fullWidth' => $this->fullWidth,
            'showCounts' => $this->showCounts,
            'showPreview' => $this->showPreview,
            'showToolbar' => $this->showToolbar,
            'extraFields' => $this->extraFields
        ]);
    }

    public function authorize(Request $request)
    {
        if(! $this->resourceClass::authorizable()) {
            return true;
        }

        if(! isset($request->resource)) {
            return false;
        }

        return call_user_func([ $this->resourceClass, 'authorizedToViewAny'], $request)
            && $request->newResource()->authorizedToAttachAny($request, $this->resourceClass::newModel())
            && parent::authorize($request);
    }

    public function height($height)
    {
        $this->height = $height;

        return $this;
    }

    public function fullWidth($fullWidth=true)
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    public function hideToolbar()
    {
        $this->showToolbar = false;

        return $this;
    }

    public function showCounts($showCounts=true)
    {
        $this->showCounts = $showCounts;

        return $this;
    }

    public function showPreview($showPreview=true)
    {
        $this->showPreview = $showPreview;

        return $this;
    }

    public function extraFields($fieldNames=[])
    {
        $this->extraFields = $fieldNames;

        return $this;
    }
}
