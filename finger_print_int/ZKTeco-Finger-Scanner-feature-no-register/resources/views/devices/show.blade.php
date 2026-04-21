@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Detail Device</h4>
                        <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary btn-sm">
                            ← Back to Devices
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <strong>Nama:</strong>
                            </div>
                            <div class="col-sm-9">
                                {{ $device->nama }}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <strong>Nomor Serial:</strong>
                            </div>
                            <div class="col-sm-9">
                                {{ $device->no_sn }}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <strong>Lokasi:</strong>
                            </div>
                            <div class="col-sm-9">
                                {{ $device->lokasi }}
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-sm-3">
                                <strong>Online:</strong>
                            </div>
                            <div class="col-sm-9">
                                <span class="badge {{ $device->online ? 'bg-success' : 'bg-danger' }}">
                                    {{ $device->online ? 'Online' : 'Offline' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="{{ route('devices.edit', $device->id) }}" class="btn btn-primary">
                                Edit Device
                            </a>
                            <form action="{{ route('devices.destroy', $device->id) }}" method="post" class="d-inline">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus device ini?')">
                                    Delete Device
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
