<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/modules.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf();
$moduleKey = (string)($_POST['module'] ?? '');
$action = (string)($_POST['action'] ?? 'save');
if (!isset($modules[$moduleKey])) { flash('danger', 'Unknown module.'); redirect('index.php'); }
require_access($moduleKey);
$module = $modules[$moduleKey];
$table = $module['table'];
$id = (int)($_POST['id'] ?? 0);

function recalc_bill(PDO $pdo, int $billId): void {
    $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE bill_id=?');
    $sum->execute([$billId]);
    $paid = (float)$sum->fetchColumn();
    $stmt = $pdo->prepare('SELECT total_amount FROM bills WHERE id=?');
    $stmt->execute([$billId]);
    $total = (float)$stmt->fetchColumn();
    $balance = max(0, $total - $paid);
    $status = $balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partially Paid' : 'Pending');
    $pdo->prepare('UPDATE bills SET paid_amount=?,balance=?,status=? WHERE id=?')->execute([$paid,$balance,$status,$billId]);
}
function recalc_pharmacy_sale(PDO $pdo, int $saleId): void {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(line_total),0) FROM pharmacy_sale_items WHERE sale_id=?');
    $stmt->execute([$saleId]);
    $subtotal = (float)$stmt->fetchColumn();
    $sale = $pdo->prepare('SELECT discount FROM pharmacy_sales WHERE id=?');
    $sale->execute([$saleId]);
    $discount = (float)$sale->fetchColumn();
    $pdo->prepare('UPDATE pharmacy_sales SET subtotal=?,total_amount=? WHERE id=?')->execute([$subtotal,max(0,$subtotal-$discount),$saleId]);
}
function update_bed_status(PDO $pdo, ?int $bedId): void {
    if (!$bedId) return;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE bed_id=? AND status='Admitted'");
    $stmt->execute([$bedId]);
    $status = (int)$stmt->fetchColumn() > 0 ? 'Occupied' : 'Available';
    $pdo->prepare('UPDATE beds SET status=? WHERE id=?')->execute([$status,$bedId]);
}
function store_uploaded_file(string $fieldName, string $targetFolder, array $allowedMimeTypes): ?string {
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) return null;
    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('The file upload failed. Please try again.');
    if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Uploaded files must be 5 MB or smaller.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    if (!in_array($mime, $allowedMimeTypes, true) || !isset($extensions[$mime])) throw new RuntimeException('This file type is not allowed.');
    $relativeFolder = 'uploads/' . trim($targetFolder, '/');
    $absoluteFolder = __DIR__ . '/' . $relativeFolder;
    if (!is_dir($absoluteFolder) && !mkdir($absoluteFolder, 0750, true) && !is_dir($absoluteFolder)) throw new RuntimeException('Upload folder could not be created.');
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string)$file['tmp_name'], $absoluteFolder . '/' . $filename)) throw new RuntimeException('The uploaded file could not be saved.');
    return $relativeFolder . '/' . $filename;
}

try {
    $pdo->beginTransaction();
    if ($action === 'delete') {
        if (!in_array(current_user()['role_name'] ?? '', ['super_admin','hospital_admin'], true)) throw new RuntimeException('Only a hospital administrator can delete records.');
        if ($id < 1) throw new RuntimeException('Invalid record ID.');
        $old = $pdo->prepare("SELECT * FROM {$table} WHERE id=?");
        $old->execute([$id]);
        $oldRow = $old->fetch();
        if (!$oldRow) throw new RuntimeException('Record not found.');
        if ($moduleKey === 'pharmacy_sale_items') {
            $pdo->prepare('UPDATE medicines SET quantity=quantity+? WHERE id=?')->execute([(int)$oldRow['quantity'],(int)$oldRow['medicine_id']]);
            $saleId = (int)$oldRow['sale_id'];
        }
        if ($moduleKey === 'pharmacy_sales') {
            $items = $pdo->prepare('SELECT medicine_id,quantity FROM pharmacy_sale_items WHERE sale_id=?');
            $items->execute([$id]);
            foreach ($items->fetchAll() as $item) $pdo->prepare('UPDATE medicines SET quantity=quantity+? WHERE id=?')->execute([(int)$item['quantity'],(int)$item['medicine_id']]);
        }
        if ($moduleKey === 'payments') $billId = (int)$oldRow['bill_id'];
        if ($moduleKey === 'pharmacy_sale_items') $oldSaleId = (int)$oldRow['sale_id'];
        if ($moduleKey === 'admissions') $bedId = (int)($oldRow['bed_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id=?");
        $stmt->execute([$id]);
        if ($moduleKey === 'pharmacy_sale_items') recalc_pharmacy_sale($pdo, $saleId);
        if ($moduleKey === 'payments') recalc_bill($pdo, $billId);
        if ($moduleKey === 'admissions') update_bed_status($pdo, $bedId ?: null);
        audit($pdo, 'DELETE', $moduleKey, $id, 'Deleted record');
        $pdo->commit();
        flash('success', 'Record deleted successfully.');
        redirect('index.php?page=' . $moduleKey);
    }

    if ($action !== 'save') throw new RuntimeException('Unsupported action.');
    $values = [];
    foreach (module_fields($module) as $field) {
        $name = $field['name'];
        if ($name === 'password') continue;
        if (($field['type'] ?? '') === 'file') {
            $allowed = $name === 'profile_photo' ? ['image/jpeg','image/png','image/webp'] : ['application/pdf','image/jpeg','image/png','image/webp'];
            $folder = $name === 'profile_photo' ? 'profiles' : 'lab-reports';
            $uploadedPath = store_uploaded_file($name, $folder, $allowed);
            if ($uploadedPath !== null) $values[$name] = $uploadedPath;
            continue;
        }
        $raw = $_POST[$name] ?? null;
        if (is_string($raw)) $raw = trim($raw);
        if (($field['type'] ?? '') === 'datetime-local' && $raw !== null && $raw !== '') $raw = str_replace('T',' ',$raw) . (strlen((string)$raw) === 16 ? ':00' : '');
        if (($field['type'] ?? '') === 'number' && $raw !== null && $raw !== '') {
            if (!is_numeric($raw)) throw new RuntimeException(($field['label'] ?? $name) . ' must be a number.');
            $raw = (float)$raw;
            if ($raw < 0) throw new RuntimeException(($field['label'] ?? $name) . ' cannot be negative.');
        }
        if (!empty($field['required']) && ($raw === null || $raw === '')) throw new RuntimeException(($field['label'] ?? $name) . ' is required.');
        if (($field['type'] ?? '') === 'email' && $raw !== null && $raw !== '' && !filter_var($raw,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
        if ($raw === '') $raw = null;
        if ($raw === null && empty($field['required']) && $id === 0) continue;
        $values[$name] = $raw;
    }
    $password = (string)($_POST['password'] ?? '');
    if ($moduleKey === 'users' && !$id && strlen($password) < 10) throw new RuntimeException('New user passwords must contain at least 10 characters.');
    if ($moduleKey === 'users' && $password !== '' && strlen($password) < 10) throw new RuntimeException('Passwords must contain at least 10 characters.');

    if ($moduleKey === 'appointments') {
        if (in_array($values['status'] ?? 'Pending',['Pending','Confirmed'],true)) {
            $check = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status IN ('Pending','Confirmed') AND id<>? LIMIT 1");
            $check->execute([$values['doctor_id'],$values['appointment_date'],$values['appointment_time'],$id]);
            if ($check->fetch()) throw new RuntimeException('This doctor already has an active appointment at that date and time. Choose another slot.');
        }
        $values['created_by'] = (int)current_user()['id'];
    }
    if ($moduleKey === 'reception') $values['registered_by'] = (int)current_user()['id'];
    if ($moduleKey === 'pharmacy_sales') $values['sold_by'] = (int)current_user()['id'];
    if ($moduleKey === 'payments') $values['received_by'] = (int)current_user()['id'];
    if ($moduleKey === 'bills') $values['created_by'] = (int)current_user()['id'];
    if ($moduleKey === 'announcements') $values['created_by'] = (int)current_user()['id'];

    if ($moduleKey === 'bills') {
        $subtotal = (float)($values['subtotal'] ?? 0);
        $discount = (float)($values['discount'] ?? 0);
        $tax = (float)($values['tax'] ?? 0);
        if ($discount > $subtotal) throw new RuntimeException('Discount cannot exceed the bill subtotal.');
        $total = max(0, $subtotal - $discount + $tax);
        $paidQuery = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE bill_id=?');
        $paidQuery->execute([$id]);
        $paid = $id > 0 ? (float)$paidQuery->fetchColumn() : 0.0;
        $balance = max(0, $total - $paid);
        $values['total_amount'] = $total;
        $values['paid_amount'] = $paid;
        $values['balance'] = $balance;
        $values['status'] = $balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partially Paid' : 'Pending');
    }

    if ($moduleKey === 'admissions' && ($values['status'] ?? 'Admitted') === 'Admitted' && !empty($values['bed_id'])) {
        $occupied = $pdo->prepare("SELECT COUNT(*) FROM admissions WHERE bed_id=? AND status='Admitted' AND id<>?");
        $occupied->execute([(int)$values['bed_id'],$id]);
        if ((int)$occupied->fetchColumn() > 0) throw new RuntimeException('That bed is already assigned to an admitted patient.');
    }

    if ($moduleKey === 'pharmacy_sale_items') {
        $medicineId = (int)($values['medicine_id'] ?? 0);
        $qty = (int)($values['quantity'] ?? 0);
        if ($qty < 1) throw new RuntimeException('Sale quantity must be at least 1.');
        $stock = $pdo->prepare('SELECT quantity,requires_prescription FROM medicines WHERE id=? FOR UPDATE');
        $stock->execute([$medicineId]);
        $stockRow = $stock->fetch();
        if (!$stockRow) throw new RuntimeException('Medicine record was not found.');
        $available = (int)$stockRow['quantity'];
        if ((int)$stockRow['requires_prescription'] === 1) {
            $saleCheck = $pdo->prepare('SELECT ps.prescription_id,ps.patient_id,pr.patient_id AS prescription_patient FROM pharmacy_sales ps LEFT JOIN prescriptions pr ON pr.id=ps.prescription_id WHERE ps.id=?');
            $saleCheck->execute([(int)$values['sale_id']]);
            $saleRow = $saleCheck->fetch();
            if (!$saleRow || empty($saleRow['prescription_id']) || (int)$saleRow['patient_id'] !== (int)$saleRow['prescription_patient']) throw new RuntimeException('This medicine requires a valid prescription for the same patient.');
        }
        if ($id) {
            $prior = $pdo->prepare('SELECT medicine_id,quantity FROM pharmacy_sale_items WHERE id=?');
            $prior->execute([$id]); $priorRow = $prior->fetch();
            if ($priorRow) {
                $pdo->prepare('UPDATE medicines SET quantity=quantity+? WHERE id=?')->execute([(int)$priorRow['quantity'],(int)$priorRow['medicine_id']]);
                if ((int)$priorRow['medicine_id'] === $medicineId) $available += (int)$priorRow['quantity'];
            }
        }
        if ($available < $qty) throw new RuntimeException('Insufficient stock. Available quantity: ' . $available . '.');
        $values['line_total'] = $qty * (float)$values['unit_price'];
        $saleId = (int)$values['sale_id'];
    }
    if ($moduleKey === 'payments') {
        $amount = (float)($values['amount'] ?? 0);
        if ($amount <= 0) throw new RuntimeException('Payment amount must be greater than zero.');
        $totalStmt = $pdo->prepare('SELECT total_amount FROM bills WHERE id=?');
        $totalStmt->execute([(int)$values['bill_id']]);
        $billTotal = (float)$totalStmt->fetchColumn();
        $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE bill_id=? AND id<>?');
        $paidStmt->execute([(int)$values['bill_id'],$id]);
        if ((float)$paidStmt->fetchColumn() + $amount > $billTotal + 0.001) throw new RuntimeException('Payment exceeds the outstanding invoice balance.');
    }

    if ($moduleKey === 'users' && $password !== '') $values['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

    if ($id > 0) {
        $oldQuery = $pdo->prepare("SELECT * FROM {$table} WHERE id=?");
        $oldQuery->execute([$id]);
        $oldRow = $oldQuery->fetch();
        if (!$oldRow) throw new RuntimeException('Record not found.');
        if ($moduleKey === 'payments') $billId = (int)$oldRow['bill_id'];
        if ($moduleKey === 'pharmacy_sale_items') $oldSaleId = (int)$oldRow['sale_id'];
        if ($moduleKey === 'admissions') $oldBedId = (int)($oldRow['bed_id'] ?? 0);
        if ($moduleKey === 'pharmacy_sales') unset($values['sold_by']);
        if ($moduleKey === 'payments') unset($values['received_by']);
        if ($moduleKey === 'bills') unset($values['created_by']);
        if ($moduleKey === 'announcements') unset($values['created_by']);
        if ($moduleKey === 'reception') unset($values['registered_by']);
        if ($moduleKey === 'appointments') unset($values['created_by']);
        if ($moduleKey === 'users' && $password === '') unset($values['password_hash']);
        if ($moduleKey === 'users' && isset($values['is_active']) && (int)$values['is_active'] === 0 && (int)$oldRow['id'] === (int)current_user()['id']) throw new RuntimeException('You cannot disable your own active account.');
        $assignments = [];
        foreach (array_keys($values) as $column) $assignments[] = "{$column}=?";
        if (!$assignments) throw new RuntimeException('No editable fields were submitted.');
        $stmt = $pdo->prepare("UPDATE {$table} SET " . implode(',', $assignments) . ' WHERE id=?');
        $stmt->execute([...array_values($values),$id]);
        if ($moduleKey === 'payments') { recalc_bill($pdo, $billId); if ((int)$values['bill_id'] !== $billId) recalc_bill($pdo, (int)$values['bill_id']); }
        if ($moduleKey === 'admissions') { update_bed_status($pdo, $oldBedId ?: null); update_bed_status($pdo, (int)($values['bed_id'] ?? 0) ?: null); }
        if ($moduleKey === 'lab_results') $pdo->prepare("UPDATE lab_orders SET status='Completed' WHERE id=?")->execute([(int)$values['lab_order_id']]);
        if ($moduleKey === 'pharmacy_sale_items') {
            $pdo->prepare('UPDATE medicines SET quantity=quantity-? WHERE id=?')->execute([(int)$values['quantity'],(int)$values['medicine_id']]);
            recalc_pharmacy_sale($pdo, $oldSaleId); if ($saleId !== $oldSaleId) recalc_pharmacy_sale($pdo, $saleId);
        }
        audit($pdo, 'UPDATE', $moduleKey, $id, 'Updated record');
        $message = 'Record updated successfully.';
    } else {
        if ($moduleKey === 'users' && !isset($values['password_hash'])) throw new RuntimeException('A password is required for new user accounts.');
        $columns = array_keys($values);
        $marks = implode(',', array_fill(0,count($columns),'?'));
        $stmt = $pdo->prepare("INSERT INTO {$table} (" . implode(',',$columns) . ") VALUES ({$marks})");
        $stmt->execute(array_values($values));
        $newId = (int)$pdo->lastInsertId();
        if ($moduleKey === 'payments') recalc_bill($pdo, (int)$values['bill_id']);
        if ($moduleKey === 'admissions') update_bed_status($pdo, (int)($values['bed_id'] ?? 0) ?: null);
        if ($moduleKey === 'lab_results') $pdo->prepare("UPDATE lab_orders SET status='Completed' WHERE id=?")->execute([(int)$values['lab_order_id']]);
        if ($moduleKey === 'pharmacy_sale_items') {
            $pdo->prepare('UPDATE medicines SET quantity=quantity-? WHERE id=?')->execute([(int)$values['quantity'],(int)$values['medicine_id']]);
            recalc_pharmacy_sale($pdo, $saleId);
        }
        audit($pdo, 'CREATE', $moduleKey, $newId, 'Created record');
        $message = 'Record created successfully.';
    }
    $pdo->commit();
    flash('success', $message);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = $exception instanceof PDOException && $exception->getCode() === '23000'
        ? 'This record conflicts with an existing value or is still linked to another record. Check unique IDs and related records.'
        : $exception->getMessage();
    flash('danger', $message ?: 'The action could not be completed.');
}
redirect('index.php?page=' . $moduleKey);
