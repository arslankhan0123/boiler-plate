@include('errors.layout', [
    'code' => 405, 'title' => 'Method Not Allowed', 'icon' => 'bi-slash-circle', 'tone' => 'warning',
    'message' => "This action isn't allowed on this page. Please go back and try a different option.",
])
