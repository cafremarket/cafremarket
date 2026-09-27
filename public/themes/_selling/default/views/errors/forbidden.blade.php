{{-- Render the core view by path: including 'errors.forbidden' by name resolves back to this theme file and recurses forever. --}}
{!! view()->file(resource_path('views/errors/forbidden.blade.php'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1, '__env' => 1, 'app' => 1]))->render() !!}
