# 📚 Learnify — Adaptive Learning & Intelligent Examination Platform

[![Live Demo](https://img.shields.io/badge/Demo-Live%20Website-brightgreen?style=for-the-badge&logo=googlechrome)](https://samar-learnify.rf.gd)
[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)](LICENSE)

**Learnify** is a modern, responsive, full-stack Learning Management System (LMS) and examination engine designed for interactive computer science education. Built with a slick **Glassmorphism UI**, it brings together structured module-based learning, automated examination engines, real-time progress logging, instant certificate generation, and an embedded **AI Learning Assistant Chatbot**.

---

## 🌐 Live Web Application & Repository

- **🌐 Live Demo:** [https://samar-learnify.rf.gd](https://samar-learnify.rf.gd)
- **💻 Source Code:** [https://github.com/samar-hussain/learnify](https://github.com/samar-hussain/learnify)

---

## ✨ Key Features & Real Functionality

### 🤖 1. AI Study Assistant & Interactive Chatbot
- **Context-Aware Assistance:** Integrated AI assistant within the learning studio to help students clarify complex CS concepts in real-time.
- **Instant Query Resolution:** Guided prompt architecture for step-by-step explanations, code debugging, and topic summaries.

### 🎓 2. Student Workspace & CS Learning Modules
- **Interactive CS Curriculum:** Covers core computer science domains including Web Development, Database Systems, Software Architecture, and OOP.
- **Split-Screen Studio:** Embedded video tutorials alongside dynamic lecture notes and interactive outlines.
- **Real-Time Progress Tracking:** Automatic progress persistence per user session with interactive progress bar indicators.

### 📝 3. Non-Repeating Exam Simulator & Certification
- **Randomized Question Bank:** Smart dynamic queries preventing question repetition across attempts.
- **Timed Certification Exams:** Real-time JavaScript countdown timer with automatic force-submission on expiration.
- **Instant Result Evaluation:** Dynamic score calculation and feedback display immediately after exam completion.
- **Automated Certificate Engine:** Verifiable completion certificate generation upon achieving passing criteria.

### 🛡️ 4. Role-Based Access Control (RBAC) & Security
- **Dual Authentication System:** Separate, secure login workflows for **Students** and **Administrators**.
- **PDO Security Protocols:** Prepared statements protecting against SQL Injection and Session Hijacking.
- **Admin Control Center:** Complete admin capabilities to manage student records, add/update module content, and update question banks dynamically.

### 🎨 5. Modern UI/UX Architecture
- **Deep-Purple Glassmorphism Design:** Modern aesthetic featuring blurred backdrops, high contrast typography, and responsive flex/grid layouts.
- **Cross-Device Compatibility:** Fully optimized layout for desktops, tablets, and smartphones.

---

## 🛠️ Tech Stack

| Layer | Technologies Used |
| :--- | :--- |
| **Frontend** | HTML5, CSS3 (Custom Glassmorphism styling), JavaScript (ES6+), FontAwesome Icons |
| **Backend** | PHP 8.x (Object-Oriented & Procedural PHP with PDO Security) |
| **Database** | MySQL / MariaDB (Relational Schema with Foreign Key Constraints & Indexes) |
| **Integrations** | AI Chatbot Component, YouTube Video API Embeds |
| **Deployment** | Remote Apache Hosting, Git/GitHub Version Control |

---

## 📁 Repository Structure

```text
learnify/
├── includes/
│   ├── bootstrap.php       # Environment initialization & global handlers
│   ├── config.php          # Database PDO connection setup
│   └── header.php / footer.php
├── assets/
│   ├── css/                # Custom Glassmorphism styles & animations
│   ├── js/                 # Exam timer, progress trackers & chatbot scripts
│   └── images/             # UI badges, logos, and UI assets
├── admin/                  # Administrator control panel and management scripts
├── student/                # Student workspace endpoints
├── uploads/                # User avatars and dynamically generated assets
├── index.php               # Public landing page
├── login.php               # Secure authentication endpoint
├── register.php            # New student registration handling
├── dashboard.php           # Interactive learning workspace
├── exam.php                # Exam simulation engine
├── verify-certificate.php  # Public certificate verification route
└── README.md               # Project documentation
