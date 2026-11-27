@include('errors.layout', [
    'code' => 401, 'title' => 'Unauthorized', 'icon' => 'bi-shield-exclamation', 'tone' => 'danger',
    'message' => 'You need to sign in to view this page. Please log in and try again.',
])
