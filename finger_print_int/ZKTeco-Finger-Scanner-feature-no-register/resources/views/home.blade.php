@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Welcome to our Dashboard!</h1>
    
    <div class="row g-4">
        <!-- Devices Card -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cpu text-danger" viewBox="0 0 16 16">
                                <path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 13.5 4.5H15v1h-1.5v1H15v1h-1.5v1H15v1h-1.5a2.5 2.5 0 0 1-2.5 2.5v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14A2.5 2.5 0 0 1 2.5 11.5H1v-1h1.5v-1H1v-1h1.5v-1H1v-1h1.5A2.5 2.5 0 0 1 4.5 4.5V3a.5.5 0 0 1 .5-.5z"/>
                                <path d="M4 4a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Devices</h5>
                            <p class="card-text text-muted mb-0">Manage your fingerprint devices</p>
                        </div>
                    </div>
                    <a href="{{ route('devices.index') }}" class="btn btn-outline-danger btn-sm">
                        View Devices →
                    </a>
                </div>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people text-primary" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Attendance</h5>
                            <p class="card-text text-muted mb-0">View attendance records</p>
                        </div>
                    </div>
                    <a href="{{ route('devices.Attendance') }}" class="btn btn-outline-primary btn-sm">
                        View Records →
                    </a>
                </div>
            </div>
        </div>

        <!-- Device Logs Card -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-text text-success" viewBox="0 0 16 16">
                                <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1z"/>
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Device Logs</h5>
                            <p class="card-text text-muted mb-0">Monitor device activity</p>
                        </div>
                    </div>
                    <a href="{{ route('devices.DeviceLog') }}" class="btn btn-outline-success btn-sm">
                        View Logs →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h2 class="h4 mb-3">Quick Actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('devices.index') }}" class="btn btn-danger">
                Manage Devices
            </a>
            <a href="{{ route('devices.Attendance') }}" class="btn btn-primary">
                View Attendance
            </a>
            <a href="{{ route('devices.FingerLog') }}" class="btn btn-success">
                Finger Logs
            </a>
        </div>
    </div>
</div>
@endsection
