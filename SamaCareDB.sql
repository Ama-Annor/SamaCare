-- Create SamaCare database
CREATE DATABASE samacare;
USE samacare;


-- User roles table
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Users table (all users of the system)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    profile_image VARCHAR(255),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Patient-specific information
CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    gender ENUM('male', 'female', 'other'),
    blood_type VARCHAR(10),
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    insurance_provider VARCHAR(100),
    insurance_number VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Doctor specialties
CREATE TABLE specialties (
    specialty_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- Doctor-specific information
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialty_id INT,
    license_number VARCHAR(50) NOT NULL,
    biography TEXT,
    years_experience INT,
    rating DECIMAL(3,2),
    consultation_fee DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(specialty_id)
);

-- Doctor schedule
CREATE TABLE doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_schedule (doctor_id, day_of_week)
);

-- Clinic locations
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE
);

-- Services offered
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    duration INT,  -- in minutes
    default_cost DECIMAL(10,2)
);

-- Appointments
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service_id INT NOT NULL,
    location_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- Medical record categories
CREATE TABLE record_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Medical records
CREATE TABLE medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    category_id INT NOT NULL,
    record_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES record_categories(category_id)
);

-- Health metrics types (blood pressure, weight, etc.)
CREATE TABLE metric_types (
    metric_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    unit VARCHAR(20),
    description TEXT
);

-- Health tracking measurements
CREATE TABLE health_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    metric_type_id INT NOT NULL,
    value VARCHAR(50) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (metric_type_id) REFERENCES metric_types(metric_type_id)
);


-- FAQ categories
CREATE TABLE faq_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50)
);

-- FAQs
CREATE TABLE faqs (
    faq_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES faq_categories(category_id)
);


-- Contact messages/inquiries
CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    response_by INT NULL,
    FOREIGN KEY (response_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Health tracking metric logs with more detailed fields
CREATE TABLE health_metric_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    metric_type_id INT NOT NULL,
    systolic INT NULL, -- For blood pressure
    diastolic INT NULL, -- For blood pressure
    weight DECIMAL(5,2) NULL, -- For weight in kg
    temperature DECIMAL(4,2) NULL, -- For body temperature
    glucose INT NULL, -- For blood glucose
    heart_rate INT NULL, -- For heart rate
    value_numeric DECIMAL(10,2) NULL, -- For generic numeric values
    value_text VARCHAR(100) NULL, -- For text-based values
    status VARCHAR(50), -- Normal, High, Low, etc.
    notes TEXT,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (metric_type_id) REFERENCES metric_types(metric_type_id)
);

-- User activities for the activity feed
CREATE TABLE user_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL, -- appointment_booked, record_added, etc.
    related_id INT NULL, -- ID related to the activity (appointment_id, record_id, etc.)
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Testimonials for feature page
CREATE TABLE testimonials (
    testimonial_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    testimonial TEXT NOT NULL,
    rating INT,
    is_displayed BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Office locations (for contact page)
CREATE TABLE office_locations (
    office_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    hours TEXT,
    map_url VARCHAR(255),
    image_path VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE
);