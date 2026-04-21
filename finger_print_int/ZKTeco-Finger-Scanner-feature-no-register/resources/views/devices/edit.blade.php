@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Device</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('devices.update', $device->id) }}">
                            @csrf
                            @method('put')
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama</label>
                                <input type="text" name="nama" class="form-control" id="nama" value="{{ $device->nama }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="no_sn" class="form-label">Nomor Serial</label>
                                <input type="text" name="no_sn" class="form-control" id="no_sn" value="{{ $device->no_sn }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="lokasi" class="form-label">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control" id="lokasi" value="{{ $device->lokasi }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="online" class="form-label">Online</label>
                                <input type="text" name="online" class="form-control" id="online" value="{{ $device->online }}" required>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="{{ route('devices.index') }}" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Device</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
