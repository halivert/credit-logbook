<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class Controller extends BaseController
{
    use AuthorizesRequests {
        authorizeResource as parentAuthorize;
    }

    use ValidatesRequests;

    /**
     * Return a view without cache, to allow browser to refresh again when back
     * is pressed
     *
     * @param array<int, mixed> $data
     * @param array<string, string> $headers
     */
    public function view(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        return Inertia::render($view, $data, $status, $headers + [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string|array  $model
     * @param  string|array|null  $parameter
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function authorizeResource(
        $model,
        $parameter = null,
        array $options = [],
        $request = null
    ) {
        $model = is_array($model) ? implode(',', $model) : $model;
        $parameter = is_array($parameter) ? implode(',', $parameter) : $parameter;
        $parameter = $parameter ?: Str::camel(class_basename($model));

        $this->parentAuthorize($model, $parameter, $options, $request);
    }
}
