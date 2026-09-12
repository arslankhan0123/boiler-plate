import os

file_path = 'resources/views/admin/users/index.blade.php'
with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# lines[:9] gets lines 1-9
# lines[230:] gets line 231 onwards
new_lines = lines[:9] + ['@endsection\n', '\n'] + lines[230:]

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
