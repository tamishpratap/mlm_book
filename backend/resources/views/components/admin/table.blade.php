@props([
    'headers' => [],
    'empty' => false,
    'emptyMessage' => 'No records found.',
])

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        @if(!empty($headers))
            <thead class="table-light">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @if($empty)
                <tr>
                    <td colspan="{{ count($headers) ?: 1 }}" class="text-center py-4 text-muted">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
