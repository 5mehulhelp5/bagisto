<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Admin\DataGrids\Catalog\AttributeFamilyDataGrid;
use Webkul\Core\Rules\Code;

class AttributeFamilyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AttributeFamilyDataGrid::class)->toJson();
        }

        return view('admin::catalog.families.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOneByField('code', 'default');

        $customAttributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type', 'is_user_defined']);

        return view('admin::catalog.families.create', compact('attributeFamily', 'customAttributes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code', new Code],
            'name' => 'required',
        ]);

        Event::dispatch('catalog.attribute_family.create.before');

        $attributeFamily = $this->attributeFamilyRepository->create([
            'attribute_groups' => request('attribute_groups'),
            'code'             => request('code'),
            'name'             => request('name'),
        ]);

        Event::dispatch('catalog.attribute_family.create.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.create-success'));

        return redirect()->route('admin.catalog.families.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOrFail($id, ['*']);

        $customAttributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type']);

        return view('admin::catalog.families.edit', compact('attributeFamily', 'customAttributes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code,' . $id, new Code],
            'name' => 'required',
        ]);

        Event::dispatch('catalog.attribute_family.update.before', $id);

        $attributeFamily = $this->attributeFamilyRepository->update([
            'attribute_groups' => request('attribute_groups'),
            'code'             => request('code'),
            'name'             => request('name'),
        ], $id);

        Event::dispatch('catalog.attribute_family.update.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.update-success'));

        return redirect()->route('admin.catalog.families.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if ($this->attributeFamilyRepository->count() == 1) {
            return response()->json([
                'message' => trans('admin::app.catalog.families.last-delete-error'),
            ], 400);
        }

        if ($attributeFamily->products()->count()) {
            return response()->json([
                'message' => trans('admin::app.catalog.families.attribute-product-error'),
            ], 400);
        }

        try {
            Event::dispatch('catalog.attribute_family.delete.before', $id);

            $this->attributeFamilyRepository->delete($id);

            Event::dispatch('catalog.attribute_family.delete.after', $id);

            return response()->json([
                'message' => trans('admin::app.catalog.families.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'message' => trans('admin::app.catalog.families.delete-failed', ['name' => 'admin::app.catalog.families.family']),
        ], 500);
    }

    /**
     * Remove the specified resources from database.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy()
    {
        $suppressFlash = false;

        if (request()->isMethod('delete')) {
            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $index) {
                try {
                    Event::dispatch('catalog.attribute_family.delete.before', $index);

                    $this->attributeFamilyRepository->delete($index);

                    Event::dispatch('catalog.attribute_family.delete.after', $index);
                } catch (\Exception $e) {
                    report($e);
                    $suppressFlash = true;

                    continue;
                }
            }

            if (! $suppressFlash) {
                session()->flash('success', ('admin::app.datagrid.mass-ops.delete-success'));
            } else {
                session()->flash('info', trans('admin::app.datagrid.mass-ops.partial-action', ['resource' => trans('admin::app.catalog.families.attribute-family')]));
            }

            return redirect()->back();
        } else {
            session()->flash('error', trans('admin::app.datagrid.mass-ops.method-error'));

            return redirect()->back();
        }
    }
}
