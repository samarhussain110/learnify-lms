# 📚 Learnify — Adaptive Learning & Intelligent Examination Platform

[![Live Demo](https://img.shields.io/badge/Demo-Live%20Website-brightgreen?style=for-the-badge&logo=googlechrome)](https://samar-learnify.rf.gd)
[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql)](https://www.mysql.com/)

**Learnify** is a modern, responsive, full-stack Learning Management System (LMS) and examination engine built to deliver interactive computer science education. It integrates structured module learning, real-time exam simulations, adaptive progress tracking, and student certification in a clean, glassmorphic user interface.

---

## 🚀 Live Demo & Hosting

- **🌐 Live Web Application:** [https://samar-learnify.rf.gd](https://samar-learnify.rf.gd)
- **💻 Source Code Repository:** [GitHub Repository Link](https://github.com/samar-hussain/learnify)

---

## ✨ Key Features

### 🎓 Student Workspace & Learning Modules
- **20 Interactive CS Modules:** Comprehensive learning paths covering software engineering, backend architecture, and database management.
- **Embedded Video Lessons:** Seamlessly integrated YouTube video tutorials for quick conceptual learning.
- **Progress Tracking:** Automatic progress logging per module with visual completion indicators.

### 📝 Non-Repeating Exam Simulator & Engine
- **Randomized Question Bank:** Smart question selection algorithms preventing repetition across attempts.
- **Timed Certification Testing:** Real-time exam countdown timer and instant evaluation.
- **Automated Certificate Generation:** Automated eligibility check generating verifiable certificates upon course completion.

### 🛡️ Authentication & Role Management
- **Dual-Role Access:** Secure Student and Administrator authentication using PDO password hashing and PHP session management.
- **Admin Dashboard:** Full dynamic control over user management, lesson creation, and question bank updates.

### 🎨 UI/UX Excellence
- **Glassmorphism Design:** Modern deep-purple, high-contrast visual design focused on accessibility and focus.
- **Split-Screen Study Studio:** Dedicated study view combining lesson notes, outlines, and video content.

---

## 🛠️ Tech Stack & Architecture

- **Frontend:** HTML5, CSS3 (Custom Glassmorphism Design, Responsive Flex/Grid), JavaScript (ES6+), FontAwesome Icons
- **Backend:** PHP 8.x (Object-Oriented & Procedural PHP with PDO Security)
- **Database:** MySQL / MariaDB (Relational Schema, Foreign Key Constraints, Prepared Statements)
- **Deployment & Hosting:** InfinityFree Remote Hosting, GitHub VCS Integration

---

## 📁 Repository Structure

```text
learnify/
├── includes/
│   ├── bootstrap.php       # Environment configuration & global handlers
│   ├── config.php          # Database PDO connection setup
│   └── header.php / footer.php
├── assets/
│   ├── css/                # Custom Glassmorphism styles
│   └── js/                 # Dynamic UI scripts & exam timer handlers
├── uploads/                # Dynamic media and user avatars
├── index.php               # Main landing page
├── login.php               # Secure authentication endpoint
├── dashboard.php           # Student learning workspace
├── exam.php                # Adaptive exam simulation engine
└── README.md               # Project documentation
