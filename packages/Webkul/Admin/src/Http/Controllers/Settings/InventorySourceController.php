<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Admin\DataGrids\Settings\InventorySourcesDataGrid;
use Webkul\Inventory\Http\Requests\InventorySourceRequest;

class InventorySourceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected InventorySourceRepository $inventorySourceRepository)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(InventorySourcesDataGrid::class)->toJson();
        }

        return view('admin::settings.inventory_sources.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.inventory_sources.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(InventorySourceRequest $inventorySourceRequest)
    {
        Event::dispatch('inventory.inventory_source.create.before');

        $data = request()->only([
            'code',
            'name',
            'description',
            'latitude',
            'longitude',
            'priority',
            'contact_name',
            'contact_email',
            'contact_number',
            'contact_fax',
            'country',
            'state',
            'city',
            'street',
            'postcode',
            'status',
        ]);

        $inventorySource = $this->inventorySourceRepository->create($data);

        Event::dispatch('inventory.inventory_source.create.after', $inventorySource);

        session()->flash('success', trans('admin::app.settings.inventory_sources.create-success'));

        return redirect()->route('admin.inventory_sources.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $inventorySource = $this->inventorySourceRepository->findOrFail($id);

        return view('admin::settings.inventory_sources.edit', compact('inventorySource'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(InventorySourceRequest $inventorySourceRequest, $id)
    {
        Event::dispatch('inventory.inventory_source.update.before', $id);

        $data = $inventorySourceRequest->only([
            'code',
            'name',
            'description',
            'latitude',
            'longitude',
            'priority',
            'contact_name',
            'contact_email',
            'contact_number',
            'contact_fax',
            'country',
            'state',
            'city',
            'street',
            'postcode',
            'status',
        ]);

        $inventorySource = $this->inventorySourceRepository->update($data, $id);

        Event::dispatch('inventory.inventory_source.update.after', $inventorySource);

        session()->flash('success', trans('admin::app.settings.inventory_sources.update-success'));

        return redirect()->route('admin.inventory_sources.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->inventorySourceRepository->findOrFail($id);

        if ($this->inventorySourceRepository->count() == 1) {
            return response()->json(['message' => trans('admin::app.settings.inventory_sources.last-delete-error')], 400);
        }

        try {
            Event::dispatch('inventory.inventory_source.delete.before', $id);

            $this->inventorySourceRepository->delete($id);

            Event::dispatch('inventory.inventory_source.delete.after', $id);

            return response()->json(['message' => trans('admin::app.settings.inventory_sources.delete-success')]);
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['message' => trans('admin::app.response.delete-failed', ['name' => 'Inventory source'])], 500);
    }
}
