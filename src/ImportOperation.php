<?php

namespace Dlucca\BackpackImport;

use Illuminate\Http\Request;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;

trait ImportOperation
{
    private Importer $backpackImport;

    protected function setupImportRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/import', [
            'as'        => $routeName.'.getImport',
            'uses'      => $controller.'@showImportForm',
            'operation' => 'import',
        ]);

        Route::post($segment.'/import', [
            'as'        => $routeName.'.postImport',
            'uses'      => $controller.'@postImportFile',
            'operation' => 'import',
        ]);
    }

    protected function setupImportDefaults()
    {
        $this->backpackImport = new Importer();

        $this->crud->allowAccess('import');

        $this->setupExtraCrudMethods();

        $this->crud->operation('list', function() {
            $this->crud->addButton(
                'top', 
                'import', 
                'view', 
                $this->crud->get('import.button_view') ?? 'backpack-import::import_button', 
                'end'
            );
        });

       if (method_exists(self::class, 'setupImportOperation')) {
            $this->setupImportOperation();
       }
    }

    public function showImportForm()
    {
        $this->crud->hasAccessOrFail('import');

        $this->crud->setOperation('Import');

        // get the info for that entry
        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Import '.$this->crud->entity_name;
        $this->data['import_template_url'] = $this->crud->get('import.example_file_url');

        return view($this->crud->get('import.view') ?? 'backpack-import::upload', $this->data);
    }

    public function postImportFile(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'mimes:xlsx,ods,xls,csv'],
        ]);

        $importFile = $request->file('import_file');

        $this->backpackImport->setModel($this->crud->getModel());

        $this->backpackImport->import($importFile);

        Alert::success(__('backpack-import::messages.import_successfully'))->flash();

        return Redirect::to($this->crud->route);
    }

    protected function setupExtraCrudMethods(): void
    {
        $this->crud->macro('setImportView', function($viewPath) {
            $this->set('import.view', $viewPath);
        });

        $this->crud->macro('setExampleFileUrl', function($fileUrl) {
            $this->set('import.example_file_url', $fileUrl);
        });

        $this->crud->macro('setButtonView', function($viewPath) {
            $this->set('import.button_view', $viewPath);
        });
    }
}
