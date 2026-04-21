<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\Datatables;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Attendance;
use Carbon\Carbon;
use DB;

class DeviceController extends Controller
{
    // Menampilkan daftar device
    public function index(Request $request)
    {
        $data['lable'] = "Devices";

        // Fetch devices sorted by last handshake time
        $devices = DB::table('devices')
            ->leftJoin(
                'branch_mapping',
                'devices.no_sn',
                '=',
                'branch_mapping.serial_number'
            )
              ->select(
                'devices.id',
                'devices.no_sn',
                'devices.online',
                'branch_mapping.branch_name'
            )
            ->orderBy('devices.online', 'DESC')
            ->get();

        // Compute last handshake time and diff in minutes for each device
        $now = Carbon::now();
        foreach ($devices as $device) {
            if ($device->online) {
                $lastPing = is_numeric($device->online)
                    ? Carbon::createFromTimestamp($device->online)
                    : Carbon::parse($device->online);
                $device->last_seen = $lastPing->toDateTimeString();
                $device->last_seen_diff = $now->diffInMinutes($lastPing) . ' minute(s) ago';
            } else {
                $device->last_seen = 'Never';
                $device->last_seen_diff = 'Never';
            }
        }

        $data['log'] = $devices;
        return view('devices.index', $data);
    }

    public function DeviceLog(Request $request)
    {
        $data['lable'] = "Devices Log";
        $data['log'] = DB::table('device_log')->select('id','data','url')->orderBy('id','DESC')->get();
        
        return view('devices.log',$data);
    }
    
    public function FingerLog(Request $request)
    {
        $data['lable'] = "Finger Log";
        $data['log'] = DB::table('finger_log')->select('id','data','url')->orderBy('id','DESC')->get();
        return view('devices.log',$data);
    }
    public function Attendance() {
       //$attendances = Attendance::latest('timestamp')->orderBy('id','DESC')->paginate(15);
       $attendances = DB::table('attendances')->select('id','sn','table','stamp','employee_id','timestamp','status1','status2','status3','status4','status5')->orderBy('id','DESC')->paginate(15);

        return view('devices.attendance', compact('attendances'));
        
    }

    // // Menampilkan form tambah device
    // public function create()
    // {
    //     return view('devices.create');
    // }

    // // Menyimpan device baru ke database
    // public function store(Request $request)
    // {
    //     $device = new Device();
    //     $device->nama = $request->input('nama');
    //     $device->no_sn = $request->input('no_sn');
    //     $device->lokasi = $request->input('lokasi');
    //     $device->save();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan!');
    // }

    // // Menampilkan detail device
    // public function show($id)
    // {
    //     $device = Device::find($id);
    //     return view('devices.show', compact('device'));
    // }

    // // Menampilkan form edit device
    // public function edit($id)
    // {
    //     $device = Device::find($id);
    //     return view('devices.edit', compact('device'));
    // }

    // // Mengupdate device ke database
    // public function update(Request $request, $id)
    // {
    //     $device = Device::find($id);
    //     $device->nama = $request->input('nama');
    //     $device->no_sn = $request->input('no_sn');
    //     $device->lokasi = $request->input('lokasi');
    //     $device->save();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil diupdate!');
    // }

    // // Menghapus device dari database
    // public function destroy($id)
    // {
    //     $device = Device::find($id);
    //     $device->delete();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus!');
    // }
}
