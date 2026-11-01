@include('errors.layout', [
    'code' => 500, 'title' => 'Something Went Wrong', 'icon' => 'bi-exclamation-octagon', 'tone' => 'danger',
    'message' => 'An unexpected error occurred. Please try again in a moment.',
])
