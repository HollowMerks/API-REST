<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $services = Service::where('user_id', Auth::id())->get();

        return $this->success($services, 'Servicios listados correctamente');
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'foto_persona' => $request->foto_persona,
        ]);

        return $this->success($service, 'Servicio creado correctamente', 201);
    }

    public function show($id)
    {
        $service = Service::where('user_id', Auth::id())->find($id);

        if (! $service) {
            return $this->error('Servicio no encontrado', 404);
        }

        return $this->success($service);
    }

    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::where('user_id', Auth::id())->find($id);

        if (! $service) {
            return $this->error('Servicio no encontrado', 404);
        }

        $service->update($request->only('name', 'description', 'foto_persona'));

        return $this->success($service, 'Servicio actualizado correctamente');
    }

    public function destroy($id)
    {
        $service = Service::where('user_id', Auth::id())->find($id);

        if (! $service) {
            return $this->error('Servicio no encontrado', 404);
        }

        $service->delete();

        return response()->json(null, 204);
    }

}
