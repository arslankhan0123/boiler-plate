@include('errors.layout', [
    'code' => 404, 'title' => 'Page Not Found', 'icon' => 'bi-compass', 'tone' => 'primary',
    'message' => "The page you are looking for doesn't exist or has been moved.",
])
