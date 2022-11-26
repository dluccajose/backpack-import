@if ($crud->hasAccess('import'))
	<a href="{{ url($crud->route.'/import') }}" class="btn btn-primary" data-style="zoom-in"><span class="ladda-label"><i class="la la-file-import"></i> Importar</span></a>
@endif