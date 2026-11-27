import os

users_html_path = r'f:\Projects\boiler-plate\public\admin\users.html'
index_blade_path = r'f:\Projects\boiler-plate\resources\views\admin\users\index.blade.php'

with open(users_html_path, 'r', encoding='utf-8') as f:
    html_lines = f.readlines()

modals = html_lines[753:967]

with open(index_blade_path, 'r', encoding='utf-8') as f:
    blade_content = f.read()

insertion = "".join(modals) + """
@push('scripts')
    <script src="{{asset('admin/assets/js/pages/users.js')}}" defer></script>
@endpush
"""

blade_content = blade_content.replace("@endsection", insertion + "\n@endsection")

with open(index_blade_path, 'w', encoding='utf-8') as f:
    f.write(blade_content)
