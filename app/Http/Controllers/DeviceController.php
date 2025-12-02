<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;

class DeviceController extends Controller
{
     public function index()
    {
        $devices = Device::orderBy('id')->get();

        return view('zk.devices', compact('devices'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Device::create($data);

        return redirect()
            ->route('zk.devices.index')
            ->with('status', 'Device created successfully.');
    }

    public function update(Request $request, Device $device)
    {
        $data = $this->validatedData($request, $device->id);

        $device->update($data);

        return redirect()
            ->route('zk.devices.index')
            ->with('status', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()
            ->route('zk.devices.index')
            ->with('status', 'Device deleted successfully.');
    }

    /**
     * Shared validation rules
     */
    protected function validatedData(Request $request, ?int $ignoreId = null): array
    {
        // لو حابب تلتزم بالـ unique index (ip + port) ممكن تطورها
        $uniqueIpRule = 'unique:devices,ip';
        if ($ignoreId) {
            $uniqueIpRule .= ',' . $ignoreId;
        }

        $data = $request->validate([
            'ip'           => ['required', 'ip', $uniqueIpRule],
            'port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'branch_name'  => ['required', 'string', 'max:191'],
            // الباسورد اختياري
            'comm_password'=> ['nullable', 'string', 'max:191'],
        ]);

        // لو المستخدم سابها فاضية نخزنها null
        if (isset($data['comm_password']) && $data['comm_password'] === '') {
            $data['comm_password'] = null;
        }

        return $data;
    }
}
