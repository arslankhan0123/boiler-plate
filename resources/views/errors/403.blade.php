@include('errors.layout', [
    'code' => 403, 'title' => 'Access Denied', 'icon' => 'bi-shield-lock', 'tone' => 'danger',
    'message' => ($exception->getMessage() && $exception->getMessage() !== 'Forbidden' ? $exception->getMessage().' ' : '')
        .'Your role does not have permission to open this page. Please contact your administrator if you need access.',
])
