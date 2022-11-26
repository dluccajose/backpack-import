@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.list') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
  <div class="container-fluid">
    <h2>
      <span class="">Importar {!! $crud->getHeading() ?? $crud->entity_name_plural !!}</span>
      <small id="datatable_info_stack">{!! $crud->getSubheading() ?? '' !!}</small>
    </h2>
  </div>
@endsection

@section('content')
  <!-- Default box -->
  <div class="row">

    <!-- THE ACTUAL CONTENT -->
    <div class="{{ $crud->getListContentClass() }}">

        <div class="row mb-0">
          <div class="col-sm-6">
            @if ( $crud->buttons()->where('stack', 'top')->count() ||  $crud->exportButtons())
              <div class="d-print-none {{ $crud->hasAccess('create')?'with-border':'' }}">

                @include('crud::inc.button_stack', ['stack' => 'top'])

              </div>
            @endif
          </div>
          <div class="col-sm-6">
            <div id="datatable_search_stack" class="mt-sm-0 mt-2 d-print-none"></div>
          </div>
        </div>

        @if ($errors->any())
        <div class="alert alert-danger pb-0">
            <ul class="list-unstyled">
                @foreach ($errors->all() as $error)
                    <li><i class="la la-info-circle"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card p-3">
            <form action="/{{ $crud->route }}/import" method="post" enctype="multipart/form-data">
                @csrf
                <div class="col-md-12 mb-3">
                    <input type="file" name="import_file" class="form-control">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-block btn-primary">
                        Importar archivo
                    </button>
                </div>
    
                <div class="col-md-12 mt-3">
                    <p>
                        <a href="{{ asset($import_template_url) }}">
                            Descargar plantilla
                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
  </div>

@endsection

@section('after_styles')

@endsection

@section('after_scripts')

@endsection
