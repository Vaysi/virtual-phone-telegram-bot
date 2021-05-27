@extends('Dashboard.master')
@section('content')
    <!-- DataTables Example -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
            <div>
                کشور ها
            </div>
            <div>
                <a href="{{ route('countries.create') }}" class="btn btn-outline-primary"><i class="fa fa-plus"></i> افزودن</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>نام</th>
                        <th>قیمت</th>
                        <th>شناسه</th>
                        <th>تنظیمات</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>نام</th>
                        <th>قیمت</th>
                        <th>شناسه</th>
                        <th>تنظیمات</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    @forelse($Countries = \App\Country::orderBy('slug','ASC')->paginate(30) as $country)
                        <tr>
                            <td>{{ $country->name }}</td>
                            <td>{{ $country->price }}</td>
                            <td>{{ $country->slug }}</td>
                            <td>
                                <a href="{{ route('countries.edit',['country' => $country]) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <div class="alert alert-warning">
                            محتوایی برای نمایش وجود ندارد
                        </div>
                    @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">
                    {!! $Countries->render() !!}
                </div>
            </div>
        </div>
    </div>
@endsection