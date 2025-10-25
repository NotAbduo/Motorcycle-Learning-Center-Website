# Motorcycle Learning Center Website

## Project Overview

This is a comprehensive web application designed for a **Motorcycle Learning Center Company**. It provides a secure, centralized system for managing the core operations of the center.

**Key Features Include:**

* **Trainee and Instructor Management:** Tools to manage profiles, schedules, and assignments.
* **Training and Billing Hours Tracking:** System to log and track hours for both training sessions and billing purposes.
* **Quizzes and Assessment:** Functionality for creating, administering, and grading quizzes.
* **E-Signatures:** Implementation for digital signing of documents and waivers.
* **Secure Login:** Robust authentication system to protect user data.
* **Mobile-Friendly Admin Panel:** A responsive administration area for easy management on the go.

---

## 🚀 Getting Started: Database Setup

To run this website locally, you must first set up the database using the provided schema.

### 1. Initialize the Database

The repository contains a schema-only file to ensure no sensitive data is publicly exposed.

1.  Navigate to the `db/` folder in your project directory.
2.  Locate the schema file: `motorcycle_schema.sql`
3.  **Rename** this file to **`motorcycle.sql`**.
4.  Import this `motorcycle.sql` file into your local MySQL/MariaDB database server.

### 2. Create Your Admin Account

Since the provided SQL file only contains the database structure (schema) and no user data, you must manually create an initial administrative account to access the site.

**Important Security Note:**

The site uses a **hashed and encrypted password** for security. When you manually insert the first account into the appropriate user table in your database, you must ensure you are using the correct hashing algorithm (e.g., MD5, SHA-256, or PHP's `password_hash()`) to generate the password hash before inserting it.

-- Replace the placeholders with your actual data:
-- 1. [NATIONAL_ID]
-- 2. [NAME]
-- 3. [EMAIL]
-- 4. [BCRYPT_HASH_STRING] - Use a tool to generate a hash for your password!
-- 5. [PHONE_NUMBER]

INSERT INTO employees 
(national_id, name, email, password, phone_number, role, is_active) 
VALUES 
('[NATIONAL_ID]', '[NAME]', '[EMAIL]', '[BCRYPT_HASH_STRING]', '[PHONE_NUMBER]', 'supervisor', 1);

Once the account is created, you can log in and begin using the admin panel.
