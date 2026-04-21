@extends('layouts.app')  {{-- Asumsikan Anda memiliki layout utama --}}

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Attendance</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered data-table">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>SN</th>
                            <th>Employee ID</th>
                            <th>Timestamp</th>
                            <th>Status 1</th>
                            <th>Status 2</th>
                            <th>Status 3</th>
                            <th>Status 4</th>
                            <th>Status 5</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->id }}</td>
                                <td>{{ $attendance->sn }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $attendance->employee_id }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $attendance->timestamp }}</small>
                                </td>
                                <td>{{ $attendance->status1 }}</td>
                                <td>{{ $attendance->status2 }}</td>
                                <td>{{ $attendance->status3 }}</td>
                                <td>{{ $attendance->status4 }}</td>
                                <td>{{ $attendance->status5 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- source: https://stackoverflow.com/a/70119390 -->
    <div class="d-flex justify-content-center mt-4">
        {{ $attendances->links() }}  {{-- Tampilkan pagination jika ada --}}
    </div>
</div>
@endsection