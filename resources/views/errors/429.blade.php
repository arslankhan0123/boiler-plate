@include('errors.layout', [
    'code' => 429, 'title' => 'Too Many Requests', 'icon' => 'bi-hourglass-split', 'tone' => 'warning',
    'message' => "You've made too many requests in a short time. Please wait a moment and try again.",
])
