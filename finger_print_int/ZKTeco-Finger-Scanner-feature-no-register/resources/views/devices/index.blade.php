@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $lable }}</h2>
            {{-- <a href="{{ route('devices.create') }}" class="btn btn-primary">Tambah Device</a> --}}
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" id="devices">
                        <thead class="table-dark">
                            <tr>
                                {{-- <th>No</th> --}}
                                <th>Serial Number</th>
                                <th>Branch Name</th>
                                <th>Last Registered</th>
                                <th>Last Seen</th>
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($log as $d)
                                <tr>
                                    {{-- <td>{{ $d->id }}</td> --}}
                                    <td>{{ $d->no_sn }}</td>
                                    <td>{{ $d->branch_name ?? '-' }}</td>
                                    <td>{{ $d->last_seen }}</td>
                                    <td>{{ $d->last_seen_diff }}</td>
                                    <!-- Action button removed for consistent UI -->
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function refreshDeviceStatus() {
        fetch("{{ route('devices.index') }}?ajax=1")
            .then(response => response.text())
            .then(html => {
                document.getElementById("device-list").innerHTML = html;
            })
            .catch(err => console.error(err));
    }

    // Refresh every 30 seconds
    setInterval(refreshDeviceStatus, 30000);
</script>
@endpush
