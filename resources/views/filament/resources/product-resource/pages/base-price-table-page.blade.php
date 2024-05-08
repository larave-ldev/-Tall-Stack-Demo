@section('content')
    <div class="container">
        <h1>Filament Information List</h1>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Material Type</th>
                <th>Diameter (mm)</th>
                <th>Color</th>
                {{-- Add more table headers as needed --}}
            </tr>
            </thead>
            <tbody>
            {{--            @foreach ($filamentList as $filament)--}}
            {{--                <tr>--}}
            {{--                    <td>{{ $filament->name }}</td>--}}
            {{--                    <td>{{ $filament->material_type }}</td>--}}
            {{--                    <td>{{ $filament->diameter }}</td>--}}
            {{--                    <td>{{ $filament->color }}</td>--}}
            {{--                    --}}{{-- Add more table data columns as needed --}}
            {{--                </tr>--}}
            {{--            @endforeach--}}
            </tbody>
        </table>
    </div>
@endsection
