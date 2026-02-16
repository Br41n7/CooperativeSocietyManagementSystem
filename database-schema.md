# Cooperative Society Management System - Database Schema

## Database Tables Overview

The system requires 13 main tables to support all features:

1. **users** - Authentication and role management
2. **members** - Member information and profiles
3. **roles** - Role definitions and permissions
4. **savings** - Member savings and contributions
5. **loans** - Loan applications and details
6. **loan_repayments** - Loan repayment schedules and payments
7. **transactions** - All financial transactions
8. **documents** - Document storage and management
9. **meetings** - Meeting records
10. **meeting_attendance** - Meeting attendance tracking
11. **votes** - Voting/poll records
12. **notifications** - User notifications
13. **activity_logs** - System audit trail

## Table Relationships

- User has one Member
- Member belongs to User
- Member has many Savings
- Member has many Loans
- Loan has many LoanRepayments
- Member has many Transactions
- Loan generates Transactions
- Savings generate Transactions
- Meeting has many Attendance records
- Meeting has many Votes
- Notification belongs to User
- ActivityLog belongs to User

## Detailed Table Structures

### 1. users table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### 2. roles table
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    permissions JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default roles:
-- 1: super_admin - All permissions
-- 2: chairman - Approve loans, approve members
-- 3: secretary - Approve loans, manage documents, meetings
-- 4: treasurer - Manage savings, approve loans, financial reports
-- 5: member - View own data, apply for loans, contributions
```

### 3. members table
```sql
CREATE TABLE members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'Nigeria',
    occupation VARCHAR(100) NULL,
    employer VARCHAR(200) NULL,
    monthly_income DECIMAL(12, 2) NULL,
    next_of_kin_name VARCHAR(200) NOT NULL,
    next_of_kin_phone VARCHAR(20) NOT NULL,
    next_of_kin_relationship VARCHAR(100) NOT NULL,
    next_of_kin_address TEXT NULL,
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    bank_account_name VARCHAR(200) NULL,
    profile_photo VARCHAR(255) NULL,
    id_document VARCHAR(255) NULL,
    signature VARCHAR(255) NULL,
    status ENUM('pending', 'active', 'inactive', 'suspended', 'defaulting') DEFAULT 'pending',
    membership_date DATE NULL,
    last_contribution_date DATE NULL,
    is_defaulting BOOLEAN DEFAULT 0,
    credit_score INT DEFAULT 100,
    total_savings DECIMAL(12, 2) DEFAULT 0.00,
    total_loans_taken DECIMAL(12, 2) DEFAULT 0.00,
    total_loans_repaid DECIMAL(12, 2) DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_number (member_number),
    INDEX idx_status (status),
    INDEX idx_is_defaulting (is_defaulting),
    INDEX idx_membership_date (membership_date)
);
```

### 4. savings table
```sql
CREATE TABLE savings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    contribution_type ENUM('monthly', 'voluntary', 'fixed', 'penalty', 'refund') NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'online', 'deduction') NOT NULL,
    payment_date DATE NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    receipt_number VARCHAR(50) NULL,
    receipt_generated BOOLEAN DEFAULT 0,
    notes TEXT NULL,
    is_adjusted BOOLEAN DEFAULT 0,
    original_savings_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (original_savings_id) REFERENCES savings(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_member_id (member_id),
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_payment_date (payment_date),
    INDEX idx_contribution_type (contribution_type)
);
```

### 5. loans table
```sql
CREATE TABLE loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    loan_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    interest_type ENUM('flat', 'reducing', 'compound') DEFAULT 'flat',
    total_interest DECIMAL(12, 2) NOT NULL,
    total_repayment DECIMAL(12, 2) NOT NULL,
    purpose TEXT NOT NULL,
    repayment_period INT NOT NULL,
    repayment_frequency ENUM('weekly', 'bi-weekly', 'monthly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_repayment DECIMAL(12, 2) NOT NULL,
    collateral TEXT NULL,
    guarantor_name VARCHAR(200) NOT NULL,
    guarantor_phone VARCHAR(20) NOT NULL,
    guarantor_address TEXT NULL,
    guarantor_member_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'secretary_approved', 'chairman_approved', 'approved', 'rejected', 'disbursed', 'active', 'completed', 'defaulted') DEFAULT 'pending',
    disbursement_date DATE NULL,
    disbursement_method VARCHAR(50) NULL,
    disbursement_reference VARCHAR(100) NULL,
    secretary_approved_at TIMESTAMP NULL,
    secretary_approved_by BIGINT UNSIGNED NULL,
    chairman_approved_at TIMESTAMP NULL,
    chairman_approved_by BIGINT UNSIGNED NULL,
    treasurer_approved_at TIMESTAMP NULL,
    treasurer_approved_by BIGINT UNSIGNED NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (guarantor_member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (secretary_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (chairman_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (treasurer_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_loan_number (loan_number),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
);
```

### 6. loan_repayments table
```sql
CREATE TABLE loan_repayments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    due_amount DECIMAL(12, 2) NOT NULL,
    principal_amount DECIMAL(12, 2) NOT NULL,
    interest_amount DECIMAL(12, 2) NOT NULL,
    paid_amount DECIMAL(12, 2) DEFAULT 0.00,
    payment_date DATE NULL,
    payment_method ENUM('cash', 'bank_transfer', 'online', 'savings_deduction') NULL,
    transaction_number VARCHAR(50) NULL,
    receipt_number VARCHAR(50) NULL,
    status ENUM('pending', 'paid', 'partial', 'overdue', 'defaulted') DEFAULT 'pending',
    days_overdue INT DEFAULT 0,
    penalty_amount DECIMAL(12, 2) DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);
```

### 7. transactions table
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_type ENUM('savings', 'loan_disbursement', 'loan_repayment', 'withdrawal', 'penalty', 'refund', 'expense', 'income') NOT NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_type VARCHAR(50) NULL,
    member_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12, 2) NOT NULL,
    description TEXT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NOT NULL,
    is_reversed BOOLEAN DEFAULT 0,
    reversed_at TIMESTAMP NULL,
    reversed_by BIGINT UNSIGNED NULL,
    reversal_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (reversed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_member_id (member_id),
    INDEX idx_transaction_date (transaction_date)
);
```

### 8. documents table
```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    document_type ENUM('constitution', 'meeting_minutes', 'loan_agreement', 'member_id', 'financial_report', 'policy', 'other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    member_id BIGINT UNSIGNED NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    meeting_id BIGINT UNSIGNED NULL,
    loan_id BIGINT UNSIGNED NULL,
    is_public BOOLEAN DEFAULT 0,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    INDEX idx_document_type (document_type),
    INDEX idx_member_id (member_id),
    INDEX idx_is_public (is_public)
);
```

### 9. meetings table
```sql
CREATE TABLE meetings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    meeting_type ENUM('agm', 'executive', 'committee', 'emergency', 'regular') NOT NULL,
    meeting_date DATETIME NOT NULL,
    venue VARCHAR(255) NOT NULL,
    agenda TEXT NOT NULL,
    minutes TEXT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    notify_members BOOLEAN DEFAULT 1,
    total_attendees INT DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_meeting_date (meeting_date),
    INDEX idx_status (status)
);
```

### 10. meeting_attendance table
```sql
CREATE TABLE meeting_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    status ENUM('present', 'absent', 'excused') DEFAULT 'absent',
    check_in_time TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (meeting_id, member_id),
    INDEX idx_meeting_id (meeting_id),
    INDEX idx_member_id (member_id)
);
```

### 11. votes table
```sql
CREATE TABLE votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id BIGINT UNSIGNED NOT NULL,
    question VARCHAR(500) NOT NULL,
    vote_type ENUM('yes_no', 'multiple_choice', 'open') DEFAULT 'yes_no',
    options JSON NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    total_votes INT DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_meeting_id (meeting_id),
    INDEX idx_status (status)
);

CREATE TABLE vote_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vote_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    response VARCHAR(255) NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (vote_id, member_id)
);
```

### 12. notifications table
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('loan_approval', 'loan_rejection', 'contribution', 'repayment_reminder', 'meeting', 'announcement', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read BOOLEAN DEFAULT 0,
    read_at TIMESTAMP NULL,
    sent_email BOOLEAN DEFAULT 0,
    sent_sms BOOLEAN DEFAULT 0,
    email_sent_at TIMESTAMP NULL,
    sms_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read)
);
```

### 13. activity_logs table
```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    model_type VARCHAR(100) NULL,
    model_id BIGINT UNSIGNED NULL,
    description TEXT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_model (model_type, model_id),
    INDEX idx_created_at (created_at)
);
```

## Indexes Summary

- All tables have proper indexes for frequently queried fields
- Foreign keys are properly indexed
- Unique constraints on critical fields (member_number, loan_number, etc.)
- Composite indexes for date-based queries
- Full-text indexes on text fields for search functionality

## Database Optimization

- Use INNODB engine for all tables (transaction support)
- Partition large tables by year (loans, savings, transactions)
- Use appropriate data types to minimize storage
- Implement read replicas for reporting queries
- Archive old records (older than 5 years) to separate tables