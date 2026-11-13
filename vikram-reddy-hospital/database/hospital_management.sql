CREATE DATABASE IF NOT EXISTS hospital_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_management;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Import into a new, empty database. This script intentionally does not drop existing tables.

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  location VARCHAR(160) NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  doctor_code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  profile_photo VARCHAR(255) NULL,
  qualification VARCHAR(160) NULL,
  specialization VARCHAR(120) NULL,
  experience_years TINYINT UNSIGNED DEFAULT 0,
  phone VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  available_days VARCHAR(120) NULL,
  available_from TIME NULL,
  available_to TIME NULL,
  department_id INT NULL,
  status ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_doctors_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_doctor_name (name), INDEX idx_doctor_department (department_id)
) ENGINE=InnoDB;

CREATE TABLE patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  age TINYINT UNSIGNED NULL,
  gender ENUM('Male','Female','Other') NULL,
  dob DATE NULL,
  blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') DEFAULT 'Unknown',
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(160) NULL,
  address TEXT NULL,
  emergency_contact VARCHAR(40) NULL,
  medical_history TEXT NULL,
  allergies TEXT NULL,
  doctor_id INT NULL,
  department_id INT NULL,
  registration_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_patients_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  CONSTRAINT fk_patients_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_patient_name (name), INDEX idx_patient_phone (phone), INDEX idx_patient_registration (registration_date)
) ENGINE=InnoDB;

CREATE TABLE staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  employee_code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  role_title ENUM('Nurse','Receptionist','Pharmacist','Laboratory Technician','Ward Boy','Security','Administrative','Other') NOT NULL,
  department VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  joining_date DATE NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  department_id INT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  reason TEXT NULL,
  token_number VARCHAR(30) NULL,
  status ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  created_by INT NULL,
  active_slot_key VARCHAR(180) GENERATED ALWAYS AS (CASE WHEN status IN ('Pending','Confirmed') THEN CONCAT(doctor_id,'|',appointment_date,'|',appointment_time) ELSE NULL END) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  CONSTRAINT fk_appointments_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_appointments_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_appointment_active_slot (active_slot_key),
  INDEX idx_appointment_date_status (appointment_date,status)
) ENGINE=InnoDB;

CREATE TABLE reception (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  appointment_id INT NULL,
  check_in_time DATETIME NOT NULL,
  token_number VARCHAR(30) NULL,
  queue_status ENUM('Waiting','In Consultation','Completed','Left') NOT NULL DEFAULT 'Waiting',
  enquiry_notes TEXT NULL,
  registered_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reception_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_reception_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  CONSTRAINT fk_reception_user FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_name VARCHAR(140) NOT NULL,
  contact_person VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  address TEXT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_code VARCHAR(40) NOT NULL UNIQUE,
  medicine_name VARCHAR(140) NOT NULL,
  category VARCHAR(100) NULL,
  manufacturer VARCHAR(140) NULL,
  batch_number VARCHAR(80) NULL,
  expiry_date DATE NULL,
  purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 10,
  requires_prescription TINYINT(1) NOT NULL DEFAULT 0,
  supplier_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_medicines_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  INDEX idx_medicine_name (medicine_name), INDEX idx_medicine_expiry (expiry_date)
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prescription_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  appointment_id INT NULL,
  diagnosis TEXT NOT NULL,
  instructions TEXT NULL,
  prescribed_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prescriptions_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_prescriptions_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  CONSTRAINT fk_prescriptions_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE prescription_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT NOT NULL,
  medicine_id INT NULL,
  medicine_name VARCHAR(140) NOT NULL,
  dosage VARCHAR(120) NOT NULL,
  frequency VARCHAR(120) NULL,
  duration VARCHAR(80) NULL,
  quantity INT NOT NULL DEFAULT 1,
  instructions TEXT NULL,
  CONSTRAINT fk_prescription_items_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
  CONSTRAINT fk_prescription_items_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE pharmacy_sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NULL,
  prescription_id INT NULL,
  sold_at DATETIME NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('Cash','UPI','Card','Bank Transfer') DEFAULT 'Cash',
  sold_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
  CONSTRAINT fk_sales_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL,
  CONSTRAINT fk_sales_user FOREIGN KEY (sold_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE pharmacy_sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  medicine_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES pharmacy_sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_sale_items_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

CREATE TABLE bills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  appointment_id INT NULL,
  admission_id INT NULL,
  bill_date DATE NOT NULL,
  category ENUM('Consultation','Registration','Laboratory','Pharmacy','Room Charges','Surgery','Emergency','Other') NOT NULL DEFAULT 'Other',
  description TEXT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('Paid','Partially Paid','Pending') NOT NULL DEFAULT 'Pending',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bills_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_bills_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  CONSTRAINT fk_bills_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_bills_date_status (bill_date,status)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_code VARCHAR(40) NOT NULL UNIQUE,
  bill_id INT NOT NULL,
  payment_date DATETIME NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash','UPI','Card','Bank Transfer') NOT NULL,
  transaction_reference VARCHAR(120) NULL,
  received_by INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_bill FOREIGN KEY (bill_id) REFERENCES bills(id),
  CONSTRAINT fk_payments_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE laboratory_tests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  test_code VARCHAR(40) NOT NULL UNIQUE,
  test_name VARCHAR(140) NOT NULL,
  category VARCHAR(100) NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  turnaround_hours SMALLINT UNSIGNED DEFAULT 24,
  description TEXT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

CREATE TABLE lab_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  test_id INT NOT NULL,
  doctor_id INT NULL,
  ordered_at DATETIME NOT NULL,
  priority ENUM('Normal','Urgent','Emergency') NOT NULL DEFAULT 'Normal',
  status ENUM('Requested','Sample Collected','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  notes TEXT NULL,
  CONSTRAINT fk_lab_orders_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_lab_orders_test FOREIGN KEY (test_id) REFERENCES laboratory_tests(id),
  CONSTRAINT fk_lab_orders_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE lab_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab_order_id INT NOT NULL UNIQUE,
  result_text TEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  reported_at DATETIME NOT NULL,
  reported_by INT NULL,
  remarks TEXT NULL,
  CONSTRAINT fk_lab_results_order FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_results_user FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(30) NOT NULL UNIQUE,
  room_type ENUM('General Ward','Private Room','Semi-Private Room','ICU','Emergency Ward') NOT NULL,
  floor VARCHAR(30) NULL,
  daily_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('Available','Occupied','Maintenance') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE beds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bed_number VARCHAR(30) NOT NULL UNIQUE,
  room_id INT NOT NULL,
  status ENUM('Available','Occupied','Maintenance') NOT NULL DEFAULT 'Available',
  notes VARCHAR(255) NULL,
  CONSTRAINT fk_beds_room FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;

CREATE TABLE admissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admission_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  doctor_id INT NULL,
  room_id INT NULL,
  bed_id INT NULL,
  admitted_at DATETIME NOT NULL,
  discharged_at DATETIME NULL,
  reason TEXT NULL,
  status ENUM('Admitted','Discharged','Transferred') NOT NULL DEFAULT 'Admitted',
  CONSTRAINT fk_admissions_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_admissions_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  CONSTRAINT fk_admissions_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
  CONSTRAINT fk_admissions_bed FOREIGN KEY (bed_id) REFERENCES beds(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE doctor_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  schedule_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  slot_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  status ENUM('Available','Leave','Holiday') NOT NULL DEFAULT 'Available',
  CONSTRAINT fk_schedules_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  UNIQUE KEY uq_doctor_schedule (doctor_id,schedule_date,start_time)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  check_in TIME NULL,
  check_out TIME NULL,
  status ENUM('Present','Absent','Leave','Half Day') NOT NULL DEFAULT 'Present',
  remarks VARCHAR(255) NULL,
  CONSTRAINT fk_attendance_staff FOREIGN KEY (staff_id) REFERENCES staff(id),
  UNIQUE KEY uq_staff_attendance (staff_id,attendance_date)
) ENGINE=InnoDB;

CREATE TABLE ambulance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_number VARCHAR(40) NOT NULL UNIQUE,
  driver_name VARCHAR(120) NOT NULL,
  driver_phone VARCHAR(30) NULL,
  vehicle_type VARCHAR(80) NULL,
  status ENUM('Available','On Duty','Maintenance') NOT NULL DEFAULT 'Available',
  last_service_date DATE NULL,
  notes TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE blood_bank (
  id INT AUTO_INCREMENT PRIMARY KEY,
  blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  units_available INT NOT NULL DEFAULT 0,
  collection_date DATE NULL,
  expiry_date DATE NULL,
  donor_reference VARCHAR(100) NULL,
  status ENUM('Available','Reserved','Expired') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipment_name VARCHAR(140) NOT NULL,
  asset_code VARCHAR(60) NOT NULL UNIQUE,
  department_id INT NULL,
  purchase_date DATE NULL,
  condition_status ENUM('Good','Needs Service','Out of Order') NOT NULL DEFAULT 'Good',
  next_service_date DATE NULL,
  notes TEXT NULL,
  CONSTRAINT fk_equipment_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE medical_certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  certificate_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  certificate_type ENUM('Fitness','Medical Leave','Discharge','Other') NOT NULL,
  issue_date DATE NOT NULL,
  details TEXT NOT NULL,
  CONSTRAINT fk_cert_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_cert_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id)
) ENGINE=InnoDB;

CREATE TABLE feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NULL,
  patient_name VARCHAR(120) NULL,
  rating TINYINT UNSIGNED NOT NULL,
  category VARCHAR(80) NULL,
  comments TEXT NULL,
  submitted_at DATETIME NOT NULL,
  status ENUM('New','Reviewed','Resolved') NOT NULL DEFAULT 'New',
  CONSTRAINT fk_feedback_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  audience ENUM('All','Doctors','Staff','Patients') NOT NULL DEFAULT 'All',
  start_date DATE NOT NULL,
  end_date DATE NULL,
  status ENUM('Published','Draft','Archived') NOT NULL DEFAULT 'Published',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_name VARCHAR(140) NOT NULL,
  holiday_date DATE NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  notification_type ENUM('Info','Warning','Success','Urgent') NOT NULL DEFAULT 'Info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(80) NOT NULL,
  record_id BIGINT NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

ALTER TABLE bills ADD CONSTRAINT fk_bills_admission FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO roles (name,display_name) VALUES
('super_admin','Super Admin'),('hospital_admin','Hospital Admin'),('doctor','Doctor'),('receptionist','Receptionist'),('pharmacist','Pharmacist'),('lab_technician','Laboratory Technician'),('accountant','Accountant'),('nurse','Nurse');

-- Demo password for all demo logins: Admin@12345 (change it immediately after first login).
INSERT INTO users (name,email,password_hash,role_id,is_active) VALUES
('Hospital Administrator','admin@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',2,1),
('Super Administrator','superadmin@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',1,1),
('Dr. Ravi Kumar','ravi@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',3,1),
('Front Desk','reception@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',4,1),
('Pharmacy Desk','pharmacy@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',5,1),
('Lab Technician','lab@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',6,1),
('Accounts Desk','accounts@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',7,1),
('Nursing Desk','nurse@vikramreddyhospital.in','$2y$12$LkzZwhOjUvWmzoJTnSA5s.D81UWDUMioTkGR.QBrCzcJcmXQjMEte',8,1);

INSERT INTO departments (name,description,phone,location) VALUES
('Cardiology','Heart and cardiovascular care','0870-2401001','Block A'),('Neurology','Brain and nervous system care','0870-2401002','Block A'),('Orthopedics','Bone and joint care','0870-2401003','Block B'),('Pediatrics','Child healthcare','0870-2401004','Block B'),('General Medicine','Primary and adult medical care','0870-2401005','Block A'),('Dermatology','Skin and hair care','0870-2401006','Block C'),('ENT','Ear, nose and throat care','0870-2401007','Block C'),('Gynecology','Women health and maternity care','0870-2401008','Block B'),('Dental','Dental and oral care','0870-2401009','Block C'),('Ophthalmology','Eye care','0870-2401010','Block C'),('Emergency','24/7 emergency services','0870-2401011','Ground Floor'),('Radiology','Imaging and diagnostics','0870-2401012','Ground Floor'),('Surgery','Surgical services','0870-2401013','Block D'),('Physiotherapy','Rehabilitation and physiotherapy','0870-2401014','Block D'),('Urology','Urinary system care','0870-2401015','Block D');

INSERT INTO doctors (user_id,doctor_code,name,qualification,specialization,experience_years,phone,email,consultation_fee,available_days,available_from,available_to,department_id,status) VALUES
(3,'DOC001','Dr. Ravi Kumar','MD, DM','Cardiologist',14,'9876501001','ravi@vikramreddyhospital.in',700,'Mon,Tue,Wed,Fri','09:00','14:00',1,'Available'),
(NULL,'DOC002','Dr. Priya Sharma','MD, DM','Neurologist',11,'9876501002','priya@vikramreddyhospital.in',800,'Mon,Wed,Thu,Sat','10:00','16:00',2,'Available'),
(NULL,'DOC003','Dr. Arjun Reddy','MS Ortho','Orthopedic Specialist',12,'9876501003','arjun@vikramreddyhospital.in',600,'Tue,Thu,Fri,Sat','09:30','15:00',3,'Available'),
(NULL,'DOC004','Dr. Sneha Rao','MD Pediatrics','Pediatrician',9,'9876501004','sneha@vikramreddyhospital.in',550,'Mon,Tue,Thu,Sat','10:00','15:00',4,'Available'),
(NULL,'DOC005','Dr. Kiran Kumar','MBBS, MD','General Physician',16,'9876501005','kiran@vikramreddyhospital.in',400,'Mon,Tue,Wed,Thu,Fri','08:00','13:00',5,'Available'),
(NULL,'DOC006','Dr. Anjali Devi','MD Dermatology','Dermatologist',8,'9876501006','anjali@vikramreddyhospital.in',500,'Tue,Wed,Fri,Sat','11:00','17:00',6,'Available'),
(NULL,'DOC007','Dr. Suresh Babu','MS ENT','ENT Specialist',13,'9876501007','suresh@vikramreddyhospital.in',500,'Mon,Wed,Thu,Sat','09:00','14:00',7,'Available'),
(NULL,'DOC008','Dr. Lakshmi','MS OBG','Gynecologist',10,'9876501008','lakshmi@vikramreddyhospital.in',650,'Mon,Tue,Thu,Fri','10:00','16:00',8,'Available'),
(NULL,'DOC009','Dr. Naveen','BDS, MDS','Dentist',7,'9876501009','naveen@vikramreddyhospital.in',350,'Mon,Wed,Fri,Sat','10:00','18:00',9,'Available'),
(NULL,'DOC010','Dr. Manoj','MS Ophthalmology','Ophthalmologist',15,'9876501010','manoj@vikramreddyhospital.in',600,'Tue,Wed,Thu,Sat','09:00','15:00',10,'Available');

INSERT INTO patients (patient_code,name,age,gender,dob,blood_group,phone,email,address,emergency_contact,medical_history,allergies,doctor_id,department_id,registration_date) VALUES
('PAT0001','Ananya Rao',28,'Female','1998-04-16','B+','9000010001','ananya@example.com','Hanamkonda, Warangal','9000010091','No major medical history','No known allergies',1,1,CURDATE()),
('PAT0002','Rahul Varma',46,'Male','1980-02-05','O+','9000010002','rahul@example.com','Kazanipet, Warangal','9000010092','Hypertension','Penicillin',5,5,DATE_SUB(CURDATE(),INTERVAL 2 DAY)),
('PAT0003','Siri Teja',8,'Female','2018-09-12','A+','9000010003','parent@example.com','Nakkalagutta, Hanamkonda','9000010093','Seasonal asthma','Dust',4,4,DATE_SUB(CURDATE(),INTERVAL 5 DAY));

INSERT INTO suppliers (supplier_name,contact_person,phone,email,address) VALUES
('Sri Sai Medical Distributors','Ramesh','9000020001','sales@srisai.example','Warangal'),
('HealthCare Pharma','Suma','9000020002','orders@healthcare.example','Hanamkonda');

INSERT INTO medicines (medicine_code,medicine_name,category,manufacturer,batch_number,expiry_date,purchase_price,selling_price,quantity,low_stock_threshold,supplier_id) VALUES
('MED001','Paracetamol 500mg','Tablet','ABC Pharma','PCM2401',DATE_ADD(CURDATE(),INTERVAL 18 MONTH),1.50,2.50,450,50,1),
('MED002','Amoxicillin 250mg','Capsule','Health Labs','AMX2402',DATE_ADD(CURDATE(),INTERVAL 12 MONTH),4.00,6.00,18,25,2),
('MED003','Cetirizine 10mg','Tablet','ABC Pharma','CTZ2403',DATE_ADD(CURDATE(),INTERVAL 8 MONTH),1.00,2.00,120,20,1),
('MED004','ORS Sachet','Powder','Care Med','ORS2404',DATE_ADD(CURDATE(),INTERVAL 3 MONTH),8.00,12.00,8,20,2);

INSERT INTO rooms (room_number,room_type,floor,daily_charge,status) VALUES
('GW-101','General Ward','1',800,'Available'),('GW-102','General Ward','1',800,'Available'),('PR-201','Private Room','2',2500,'Available'),('SPR-202','Semi-Private Room','2',1600,'Available'),('ICU-01','ICU','Ground',5000,'Available'),('EW-01','Emergency Ward','Ground',1800,'Available');
INSERT INTO beds (bed_number,room_id,status) VALUES ('B-101A',1,'Available'),('B-101B',1,'Available'),('B-102A',2,'Available'),('B-201A',3,'Available'),('B-202A',4,'Available'),('ICU-A',5,'Available'),('ER-A',6,'Available');

INSERT INTO laboratory_tests (test_code,test_name,category,price,turnaround_hours,description) VALUES
('LAB001','Blood Test','Pathology',250,6,'Complete blood count'),('LAB002','Urine Test','Pathology',180,6,'Routine urine examination'),('LAB003','Blood Sugar','Biochemistry',100,2,'Fasting or random blood glucose'),('LAB004','Thyroid Test','Biochemistry',450,24,'Thyroid profile'),('LAB005','ECG','Cardiology',300,1,'Electrocardiogram'),('LAB006','X-Ray','Imaging',500,4,'Digital X-ray'),('LAB007','CT Scan','Imaging',2500,8,'Computed tomography'),('LAB008','MRI','Imaging',5500,24,'Magnetic resonance imaging'),('LAB009','Ultrasound','Imaging',1200,6,'Ultrasonography');

INSERT INTO staff (employee_code,name,role_title,department,phone,email,joining_date) VALUES
('EMP001','Meena Kumari','Nurse','Ward','9000030001','meena@example.com',DATE_SUB(CURDATE(),INTERVAL 2 YEAR)),
('EMP002','Sanjay Rao','Receptionist','Front Desk','9000030002','sanjay@example.com',DATE_SUB(CURDATE(),INTERVAL 1 YEAR)),
('EMP003','Pooja Reddy','Pharmacist','Pharmacy','9000030003','pooja@example.com',DATE_SUB(CURDATE(),INTERVAL 3 YEAR)),
('EMP004','Imran Khan','Laboratory Technician','Laboratory','9000030004','imran@example.com',DATE_SUB(CURDATE(),INTERVAL 18 MONTH));

INSERT INTO appointments (patient_id,doctor_id,department_id,appointment_date,appointment_time,reason,token_number,status,created_by) VALUES
(1,1,1,CURDATE(),'10:00:00','Cardiology follow-up','T-001','Confirmed',4),
(2,5,5,CURDATE(),'11:00:00','Blood pressure review','T-002','Pending',4),
(3,4,4,DATE_ADD(CURDATE(),INTERVAL 1 DAY),'10:30:00','Routine pediatric consultation','T-003','Confirmed',4);

INSERT INTO bills (bill_code,patient_id,appointment_id,bill_date,category,description,subtotal,discount,tax,total_amount,paid_amount,balance,status,created_by) VALUES
('BILL0001',1,1,CURDATE(),'Consultation','Cardiology consultation',700,0,0,700,700,0,'Paid',7),
('BILL0002',2,2,CURDATE(),'Consultation','General medicine consultation',400,0,0,400,200,200,'Partially Paid',7),
('BILL0003',3,NULL,CURDATE(),'Laboratory','Blood test',250,0,0,250,0,250,'Pending',7);
INSERT INTO payments (receipt_code,bill_id,payment_date,amount,payment_method,received_by) VALUES
('RCT0001',1,NOW(),700,'UPI',7),('RCT0002',2,NOW(),200,'Cash',7);

INSERT INTO doctor_schedules (doctor_id,schedule_date,start_time,end_time,slot_minutes,status) VALUES
(1,CURDATE(),'09:00','14:00',15,'Available'),(5,CURDATE(),'08:00','13:00',15,'Available'),(4,DATE_ADD(CURDATE(),INTERVAL 1 DAY),'10:00','15:00',15,'Available');
INSERT INTO ambulance (vehicle_number,driver_name,driver_phone,vehicle_type,status) VALUES ('TS03AB1234','Raju','9000040001','Basic Life Support','Available'),('TS03CD5678','Mahesh','9000040002','Advanced Life Support','On Duty');
INSERT INTO blood_bank (blood_group,units_available,collection_date,expiry_date,donor_reference,status) VALUES ('A+',4,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 25 DAY),'DONOR-001','Available'),('O+',7,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 28 DAY),'DONOR-002','Available'),('B-',1,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 20 DAY),'DONOR-003','Available');
INSERT INTO announcements (title,message,audience,start_date,end_date,status,created_by) VALUES ('Welcome to Vikram Reddy Hospital','Hospital Management System demonstration environment is ready.','All',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),'Published',2);

