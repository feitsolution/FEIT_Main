@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $lable }}</h2>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" id="devices">
                        <thead class="table-dark">
                            <tr>
                                <th>Id</th>
                                <th>Url</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($log as $d)
                                <tr>
                                    <td>{{ $d->id }}</td>
                                    <td>
                                        <code class="small">{{ $d->url }}</code>
                                    </td>
                                    <td>
                                        <pre class="small text-muted mb-0" style="max-width: 300px; overflow-x: auto;">{{ $d->data }}</pre>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
