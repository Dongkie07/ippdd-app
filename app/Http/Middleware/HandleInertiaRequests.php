<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Share data with every Inertia response.
     * flash.message is picked up by the toast in Departments.vue
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => [
                'message' => $request->session()->get('flash.message'),
                'error'   => $request->session()->get('flash.error'),
            ],
            'errors' => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object)[],
        ]);
    }
}