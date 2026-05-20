<table border="1" style="width:100%">
    <thead>
        <tr>
            <th>Device</th>
            <th>Age</th>
            <th>Latest Health</th>
            <th>Last Service</th>
        </tr>
    </thead>
    <tbody>
        @foreach($devices as $device)
        <tr>
            <td>{{ $device->model }} ({{ $device->serial_number }})</td>
            <td>{{ $device->age }}</td>
            <td>{{ $device->healthRecords->last()?->health_percentage ?? 'N/A' }}%</td>
            <td>{{ $device->serviceHistories->last()?->service_date ?? 'No service yet' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>