# 🎓 ASCT Fees Management System

### All Saints College of Technology, Bhopal

**ASCT Fees Management System** is a modern, secure, and responsive **College Fees Management System** developed to digitize and simplify student fee collection, semester-wise fee management, online payments, payment tracking, and receipt generation.

The system provides separate **Admin and Student portals**. Administrators can manage students, departments, courses, semesters, fee structures, payments, and reports, while students can securely view their fees, make online payments through **Razorpay**, check payment history, and download professional PDF fee receipts.

---

## 🚀 Project Overview

The **ASCT Fees Management System** is a PHP and MySQL-based web application designed for educational institutions that need a centralized platform for managing student fees.

The system replaces manual fee-management processes with a centralized digital solution.

### Main Workflow

```text
Admin
  ↓
Manage Departments
  ↓
Manage Courses
  ↓
Manage Semesters
  ↓
Create Fee Structure
  ↓
Add Students
  ↓
Student Login
  ↓
View Semester Fees
  ↓
Pay Online through Razorpay
  ↓
Payment Verification
  ↓
Database Update
  ↓
Generate PDF Receipt
```

---

# ✨ Key Features

## 👨‍💼 Admin Features

* 🔐 Secure Admin Login
* 📊 Admin Dashboard
* 👨‍🎓 Student Management
* 🏫 Department Management
* 📚 Course Management
* 📅 Semester Management
* 💰 Semester-wise Fee Structure
* 💳 Payment Management
* 📋 Student Fee Assignment
* 📈 Collection Reports
* 📉 Pending Fee Reports
* 🔎 Student Search and Filtering
* 🧾 Payment History
* 🧾 PDF Fee Receipt Generation
* 💵 Manual/Offline Payment Management
* 🔑 Student Password Management
* 📊 Chart.js Dashboard Analytics
* ⚙️ System Settings
* 📱 Responsive Admin Panel

---

# 🎓 Student Features

Students get their own secure portal.

### Student Dashboard

Students can view:

* Student Name
* Enrollment Number
* Department
* Course
* Academic Session
* Current Semester
* Total Fees
* Paid Fees
* Pending Fees

### Fee Management

Students can:

* View semester-wise fees
* View paid amount
* View pending amount
* Check payment status
* Pay pending fees online

### Online Payment

Integrated with:

**Razorpay Payment Gateway**

Students can pay using the payment methods supported by Razorpay.

### Payment History

Students can view:

* Receipt Number
* Payment Date
* Semester
* Amount
* Payment Status
* Razorpay Payment ID
* Razorpay Order ID

### Digital Receipt

After successful payment, students can download a professional PDF receipt generated using **TCPDF**.

---

# 🏫 Academic Management

The system supports structured academic management.

## Departments

Example:

* Diploma
* B.Tech
* M.Tech

The Admin can add additional departments as required.

## Courses

Courses can be associated with departments.

For example:

```text
B.Tech
 ├── Computer Science
 ├── Mechanical Engineering
 ├── Civil Engineering
 └── Electrical Engineering

Diploma
 ├── Computer Science
 ├── Mechanical Engineering
 ├── Civil Engineering
 └── Electrical Engineering
```

## Semesters

The system supports semester-wise fee management.

Example:

```text
Semester 1
Semester 2
Semester 3
Semester 4
Semester 5
Semester 6
Semester 7
Semester 8
```

The number of semesters can be configured according to the course.

---

# 💰 Fee Management

The system supports configurable semester-wise fee structures.

Fee components can include:

* Tuition Fee
* Examination Fee
* Development Fee
* Library Fee
* Laboratory Fee
* Other Fee
* Late Fee

The system calculates:

```text
Total Fee
    ↓
Paid Amount
    ↓
Pending Amount
```

Example:

| Semester   | Total Fee |    Paid | Pending | Status  |
| ---------- | --------: | ------: | ------: | ------- |
| Semester 1 |   ₹30,000 | ₹30,000 |      ₹0 | Paid    |
| Semester 2 |   ₹30,000 | ₹10,000 | ₹20,000 | Partial |
| Semester 3 |   ₹32,000 |      ₹0 | ₹32,000 | Pending |

---

# 💳 Razorpay Payment Integration

The system integrates **Razorpay Payment Gateway** for online fee collection.

### Payment Flow

```text
Student
   ↓
Select Fee
   ↓
Pay Now
   ↓
PHP Backend
   ↓
Create Razorpay Order
   ↓
Razorpay Checkout
   ↓
Student Completes Payment
   ↓
Payment Response
   ↓
Server-side Verification
   ↓
Payment Saved in MySQL
   ↓
Fee Balance Updated
   ↓
Receipt Generated
```

### Security Principle

The application does **not** rely only on the frontend payment response.

The payment is verified on the server before the transaction is treated as successful.

This helps prevent:

* Duplicate payment records
* Incorrect fee balances
* Tampered payment amounts
* Unverified transactions

---

# 🧾 PDF Fee Receipt

Professional PDF receipts are generated using **TCPDF**.

Receipt information can include:

```text
ALL SAINTS COLLEGE OF TECHNOLOGY
BHOPAL

FEE PAYMENT RECEIPT

Receipt Number
Student Name
Enrollment Number
Department
Course
Semester
Academic Session

Fee Amount
Paid Amount
Remaining Amount

Razorpay Order ID
Razorpay Payment ID
Payment Date
Payment Status

Authorized Signature
College Stamp
```

---

# 📊 Dashboard & Reports

The Admin dashboard provides important financial information.

### Dashboard Statistics

* Total Students
* Active Students
* Departments
* Courses
* Total Fee Collection
* Pending Fees
* Today's Collection
* Monthly Collection
* Recent Payments

### Chart.js

Chart.js can be used to visualize:

* Monthly fee collection
* Course-wise collection
* Department-wise collection
* Payment status
* Pending fee statistics

This provides administrators with a quick overview of the college's fee-management data.

---

# 👥 User Roles

## Admin

The Admin is responsible for managing the complete fee-management system.

### Admin can:

```text
Login
 ↓
Dashboard
 ↓
Departments
 ↓
Courses
 ↓
Semesters
 ↓
Fee Structures
 ↓
Students
 ↓
Student Fees
 ↓
Payments
 ↓
Reports
 ↓
Settings
```

---

## Student

The Student has access only to their own information.

```text
Login
 ↓
Student Dashboard
 ↓
View Profile
 ↓
View Semester Fees
 ↓
Pay Fees
 ↓
Payment History
 ↓
Download Receipt
```

---

# 🔐 Security Features

Security is an important part of the system because the application handles student and payment information.

Implemented/Recommended security practices include:

* Password hashing using PHP `password_hash()`
* Password verification using `password_verify()`
* Secure PHP sessions
* Session regeneration after login
* Role-based authorization
* Prepared SQL statements
* SQL injection protection
* XSS protection
* CSRF protection
* Server-side validation
* Server-side Razorpay payment verification
* Duplicate payment prevention
* Secure configuration management
* HTTPS for production deployment

### Important

Never upload:

```text
Razorpay Secret Key
Database Password
.env
Production API Credentials
Private Configuration
```

to a public GitHub repository.

---

# 🗄️ Database

The system uses **MySQL** as the relational database.

The major tables include:

```text
admins
departments
courses
semesters
students
fee_structures
student_fees
payments
admin_payment_logs
settings
```

### Database Relationships

```text
Departments
     ↓
   Courses
     ↓
  Semesters
     ↓
Fee Structures
     ↓
Student Fees
     ↓
  Payments
     ↑
 Students
```

The database design uses primary keys, foreign keys, indexes, constraints, and normalized relational structures.

---

# 🛠️ Technology Stack

## Frontend

* HTML5
* CSS3
* Bootstrap 5
* JavaScript
* Bootstrap Icons

## Backend

* PHP 8

## Database

* MySQL

## Libraries & Services

* Chart.js
* Razorpay Payment Gateway
* TCPDF

## Development Tools

* Visual Studio Code
* XAMPP
* phpMyAdmin
* Composer
* Git
* GitHub

---

# 🏗️ System Architecture

The application follows a three-tier architecture.

```text
┌───────────────────────────────┐
│       Presentation Layer      │
│ HTML | CSS | Bootstrap | JS   │
└───────────────┬───────────────┘
                │
                ↓
┌───────────────────────────────┐
│       Application Layer       │
│ PHP 8 | Authentication        │
│ Fee Management | Payments     │
│ Reports | Receipt Generation  │
└───────────────┬───────────────┘
                │
                ↓
┌───────────────────────────────┐
│          Data Layer           │
│            MySQL              │
└───────────────────────────────┘
```

External services:

```text
PHP Application
      │
      ├──── MySQL
      │
      ├──── Razorpay
      │
      └──── TCPDF
```

---

# 📱 Responsive Design

The application is designed using Bootstrap 5 and can be accessed from:

* 💻 Desktop
* 🖥️ Laptop
* 📱 Mobile
* 📲 Tablet

The Admin and Student dashboards are designed to provide a responsive user experience.

---

# 📸 Screenshots

Add your actual project screenshots here.

Recommended screenshots:

### 1. Login Page

```text
screenshots/login.png
```

### 2. Admin Dashboard

```text
screenshots/admin-dashboard.png
```

### 3. Student Management

```text
screenshots/student-management.png
```

### 4. Fee Structure

```text
screenshots/fee-structure.png
```

### 5. Student Dashboard

```text
screenshots/student-dashboard.png
```

### 6. Semester-wise Fees

```text
screenshots/student-fees.png
```

### 7. Razorpay Payment

```text
screenshots/razorpay-payment.png
```

### 8. Payment History

```text
screenshots/payment-history.png
```

### 9. PDF Receipt

```text
screenshots/fee-receipt.png
```

### 10. Admin Reports

```text
screenshots/reports.png
```

> Replace the paths above with your actual screenshots after creating a `screenshots` folder.

---

# 📂 Project Structure

```text
College-Fees-Management/
│
├── admin/
├── api/
├── assets/
├── auth/
├── config/
├── database/
├── includes/
├── student/
├── uploads/
├── receipts/
├── vendor/
│
├── .htaccess
├── composer.json
├── index.php
├── INSTALLATION.md
└── README.md
```

---

# ⚙️ Installation

## 1. Clone Repository

```bash
git clone https://github.com/najeebahmad07/College-Fees-Management.git
```

## 2. Enter Project Directory

```bash
cd College-Fees-Management
```

## 3. Configure Database

Create a MySQL database:

```text
asct_fees
```

Import the SQL file available inside the:

```text
database/
```

directory.

## 4. Configure PHP

Update your database configuration:

```php
$host = "localhost";
$dbname = "asct_fees";
$username = "root";
$password = "";
```

Use your own production credentials when deploying.

## 5. Configure Razorpay

Add your Razorpay credentials securely:

```text
RAZORPAY_KEY_ID
RAZORPAY_KEY_SECRET
```

Do not expose the Secret Key in frontend code.

## 6. Run the Application

For XAMPP:

```text
C:\xampp\htdocs\College-Fees-Management
```

Start:

```text
Apache
MySQL
```

Then open:

```text
http://localhost/College-Fees-Management/
```

---

# 🧪 Testing

The project should be tested for:

* Admin login
* Student login
* Invalid login
* Student creation
* Department creation
* Course creation
* Semester creation
* Fee structure
* Fee calculation
* Online payment
* Payment verification
* Failed payment
* Duplicate payment
* Receipt generation
* Payment history
* Pending fee calculation
* Unauthorized access
* CSRF protection
* SQL injection
* Responsive design

---

# 🔮 Future Enhancements

Possible future improvements include:

* Parent/Guardian Portal
* Student Mobile Application
* Parent Mobile Application
* SMS Notifications
* Email Notifications
* WhatsApp Notifications
* Scholarship Management
* Fee Concession Management
* Automatic Late Fee
* Installment Management
* Refund Management
* Hostel Fee Management
* Transport Fee Management
* Library Fine Management
* Multi-Campus Support
* Two-Factor Authentication
* Advanced Financial Analytics
* Accounting Software Integration
* Automated Database Backup
* Advanced Audit Logs

---

# 🎯 Use Cases

## Admin Use Cases

| Use Case           | Description                             |
| ------------------ | --------------------------------------- |
| Admin Login        | Securely access administration panel    |
| Manage Students    | Add, edit, search and manage students   |
| Manage Departments | Create and manage departments           |
| Manage Courses     | Create and manage courses               |
| Manage Semesters   | Configure course semesters              |
| Manage Fees        | Configure semester-wise fees            |
| View Payments      | Monitor online transactions             |
| Manual Payment     | Record offline payments                 |
| Reports            | Generate collection and pending reports |
| Receipt            | View/download student receipts          |

## Student Use Cases

| Use Case         | Description                            |
| ---------------- | -------------------------------------- |
| Student Login    | Secure student authentication          |
| View Profile     | View personal and academic information |
| View Fees        | Check semester-wise fees               |
| Pay Fees         | Make online payment                    |
| Payment History  | View previous transactions             |
| Download Receipt | Download PDF payment receipt           |
| Change Password  | Update account password                |

---

# 🎓 Academic Project

This project is developed as an academic software project for:

**All Saints College of Technology**
**Bhopal, Madhya Pradesh, India**

### Project Title

**ASCT Fees Management System**

### Project Type

**Web-Based College Fee Management and Online Payment System**

---

# 👨‍💻 Developer

### Najeeb Ahmad 07

**Developer | Web Developer | Full Stack Developer**

Technologies:

```text
PHP
MySQL
HTML
CSS
JavaScript
Bootstrap
Razorpay
Chart.js
TCPDF
Git
GitHub
```

GitHub:

**https://github.com/najeebahmad07**

Project Repository:

**https://github.com/najeebahmad07/College-Fees-Management**

---

# 📄 Project Documentation

The project documentation covers:

* Introduction
* Problem Statement
* Objectives
* Scope
* Literature Survey
* Proposed Methodology
* System Requirements
* Technology Stack
* System Architecture
* Database Design
* Features
* Modules
* Use Cases
* Security
* Testing
* Installation
* Future Enhancements
* Project Timeline
* References

---

# ⭐ Support

If you find this project useful for learning or academic purposes, consider giving the repository a ⭐ on GitHub.

---

# 📜 License

This project is intended primarily for academic and educational purposes.

Before deploying the system for real financial transactions, review and configure:

* College policies
* Payment gateway configuration
* Data protection requirements
* Authentication security
* Database backups
* Production HTTPS
* Financial reconciliation procedures

---

## 🔑 Keywords

```text
College Fees Management System
College Fee Management System
Student Fee Management System
Online College Fee Payment System
PHP College Management System
PHP Fees Management System
MySQL College Management System
Student Payment Portal
College Student Portal
Online Fee Collection System
Razorpay Fee Payment
Razorpay PHP Integration
Semester Fee Management
College Payment System
Education Management System
Student Fee Tracking
PHP MySQL Project
Bootstrap College Management System
College Fees Software
Academic Project PHP
```

---

**ASCT Fees Management System — Making College Fee Management Simple, Secure and Digital.**

**Developed by Najeeb Ahmad 07**
