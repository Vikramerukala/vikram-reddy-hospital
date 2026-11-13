<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $path = ''): string {
    $base = rtrim((string)BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}
function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return current_user() !== null; }
function require_login(): void {
    if (!is_logged_in()) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type'=>$type, 'message'=>$message]; }
function consume_flashes(): array {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    $provided = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $provided)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function role_permissions(): array {
    return [
        'super_admin' => ['*'],
        'hospital_admin' => ['*'],
        'doctor' => ['dashboard','doctors','departments','patients','appointments','prescriptions','prescription_items','lab_orders','lab_results','laboratory_tests','admissions','doctor_schedules','medical_certificates','announcements','notifications','reports'],
        'receptionist' => ['dashboard','doctors','departments','patients','appointments','reception','rooms','beds','admissions','feedback','announcements','notifications','reports'],
        'pharmacist' => ['dashboard','medicines','suppliers','pharmacy_sales','prescriptions','prescription_items','patients','notifications','reports'],
        'lab_technician' => ['dashboard','patients','laboratory_tests','lab_orders','lab_results','notifications','reports'],
        'accountant' => ['dashboard','bills','payments','pharmacy_sales','patients','admissions','notifications','reports'],
        'nurse' => ['dashboard','patients','appointments','admissions','rooms','beds','lab_orders','lab_results','notifications','reports'],
    ];
}
function can_access(string $module): bool {
    $user = current_user();
    if (!$user) return false;
    $permissions = role_permissions()[$user['role_name'] ?? ''] ?? [];
    return in_array('*', $permissions, true) || in_array($module, $permissions, true);
}
function require_access(string $module): void {
    if (!can_access($module)) {
        http_response_code(403);
        flash('danger', 'You do not have permission to access that section.');
        redirect('index.php');
    }
}
function audit(PDO $pdo, string $action, string $module, ?int $recordId = null, string $details = ''): void {
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, module, record_id, details, ip_address) VALUES (?,?,?,?,?,?)');
        $stmt->execute([current_user()['id'] ?? null, $action, $module, $recordId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $ignored) { /* Audit logging must not break the primary action. */ }
}
function money(mixed $amount): string { return '₹' . number_format((float)$amount, 2); }
function format_value(PDO $pdo, array $field, mixed $value): string {
    if ($value === null || $value === '') return '—';
    if (($field['type'] ?? '') === 'select' && isset($field['ref'])) {
        [$table, $labelColumn] = $field['ref'];
        $idColumn = $field['ref'][2] ?? 'id';
        $stmt = $pdo->prepare("SELECT {$labelColumn} FROM {$table} WHERE {$idColumn} = ? LIMIT 1");
        $stmt->execute([$value]);
        $label = $stmt->fetchColumn();
        return $label !== false ? (string)$label : '#' . (string)$value;
    }
    if (($field['type'] ?? '') === 'select' && isset($field['options'])) {
        return (string)($field['options'][(string)$value] ?? $value);
    }
    if (str_contains((string)($field['name'] ?? ''), 'amount') || str_contains((string)($field['name'] ?? ''), 'price') || str_contains((string)($field['name'] ?? ''), 'fee') || str_contains((string)($field['name'] ?? ''), 'charge')) {
        return money($value);
    }
    if (is_string($value) && strlen($value) > 75) return e(substr($value, 0, 72) . '…');
    return (string)$value;
}
function field_options(PDO $pdo, array $field): array {
    if (isset($field['options'])) return $field['options'];
    if (isset($field['ref'])) {
        [$table, $labelColumn] = $field['ref'];
        $idColumn = $field['ref'][2] ?? 'id';
        $orderColumn = $labelColumn === 'id' ? 'id' : $labelColumn;
        $stmt = $pdo->query("SELECT {$idColumn} AS id, {$labelColumn} AS label FROM {$table} ORDER BY {$orderColumn} LIMIT 1000");
        $options = [];
        foreach ($stmt->fetchAll() as $row) $options[(string)$row['id']] = (string)$row['label'];
        return $options;
    }
    return [];
}
function field_input(array $field, mixed $value, PDO $pdo): string {
    $name = (string)$field['name'];
    $type = (string)($field['type'] ?? 'text');
    $label = e($field['label'] ?? $name);
    $required = !empty($field['required']) ? 'required' : '';
    $placeholder = isset($field['placeholder']) ? 'placeholder="' . e($field['placeholder']) . '"' : '';
    $safeValue = e($value);
    $common = "name=\"{$name}\" id=\"field_{$name}\" class=\"form-control\" {$required} {$placeholder}";
    if ($type === 'textarea') return "<div class=\"mb-3\"><label class=\"form-label\" for=\"field_{$name}\">{$label}</label><textarea {$common} rows=\"3\">{$safeValue}</textarea></div>";
    if ($type === 'select') {
        $html = "<div class=\"mb-3\"><label class=\"form-label\" for=\"field_{$name}\">{$label}</label><select {$common}><option value=\"\">Select {$label}</option>";
        foreach (field_options($pdo, $field) as $key=>$optionLabel) {
            $selected = ((string)$key === (string)$value) ? 'selected' : '';
            $html .= '<option value="' . e($key) . '" ' . $selected . '>' . e($optionLabel) . '</option>';
        }
        return $html . '</select></div>';
    }
    if ($type === 'datetime-local' && $value) $safeValue = e(str_replace(' ', 'T', substr((string)$value, 0, 16)));
    if ($type === 'date' && $value) $safeValue = e(substr((string)$value, 0, 10));
    if ($type === 'time' && $value) $safeValue = e(substr((string)$value, 0, 5));
    if ($type === 'file') {
        $accept = isset($field['accept']) ? 'accept="' . e($field['accept']) . '"' : '';
        return "<div class=\"mb-3\"><label class=\"form-label\" for=\"field_{$name}\">{$label}</label><input type=\"file\" name=\"{$name}\" id=\"field_{$name}\" class=\"form-control\" {$accept}><div class=\"form-text\">Maximum file size: 5 MB. Upload a new file only when you want to replace the current one.</div></div>";
    }
    $inputType = in_array($type, ['number','email','date','time','datetime-local','password'], true) ? $type : 'text';
    $step = $inputType === 'number' ? 'step="0.01"' : '';
    return "<div class=\"mb-3\"><label class=\"form-label\" for=\"field_{$name}\">{$label}</label><input type=\"{$inputType}\" {$common} value=\"{$safeValue}\" {$step}></div>";
}
function module_fields(array $module): array { return $module['fields'] ?? []; }
function module_field(array $module, string $name): ?array {
    foreach (module_fields($module) as $field) if ($field['name'] === $name) return $field;
    return null;
}

function current_patient(): ?array { return $_SESSION['patient'] ?? null; }
function require_patient(): void {
    if (!current_patient()) {
        flash('warning', 'Please sign in to your patient account.');
        redirect('patient_login.php');
    }
}
