# 💊 An Tam Pharmacy Management System

> A web-based pharmacy management and online ordering system developed as an academic project at the University of Transport Ho Chi Minh City (UTH).

The system supports medicine management, inventory and batch tracking, pharmacy sales, online ordering, customer and employee management, reporting, and role-based access control.

---

## 🔗 Project Links

| Resource | Link |
|---|---|
| 🎥 Demo Video | [Watch Demo](https://drive.google.com/file/d/1gGH47GNhcV-GzOefzQJNRYuuEMjcGxFb/view) |
| 💻 Source Code | [GitHub Repository](https://github.com/choungocne/Pharmacy-management) |

---

## 📌 Project Overview

**An Tam Pharmacy Management System** was developed to support the daily operations of a pharmacy while also providing an online shopping experience for customers.

The project focuses on digitizing important pharmacy processes such as:

- Medicine and category management
- Supplier management
- Inventory and medicine batch tracking
- Customer management
- Pharmacy sales
- Online ordering
- Employee and account management
- Revenue and inventory reporting
- Role-based access control

The system also tracks medicine batches and expiration dates, helping pharmacy staff manage inventory more effectively and reduce risks related to expired or low-stock medicines.

### Project Information

- **Project:** An Tam Pharmacy Management System
- **Duration:** September 2025 – November 2025
- **Team Size:** 2 members
- **Team Members:**
  - Le Huy Hoang
  - Chau Thi Bich Ngoc

---

# 🎯 Project Objectives

The main objectives of the project are:

- Centralize pharmacy and medicine information.
- Simplify medicine and category management.
- Manage suppliers and customers.
- Track medicine inventory by batch and expiration date.
- Support pharmacy sales and invoice creation.
- Support online ordering for customers.
- Automatically update inventory after sales.
- Prevent sales when requested quantity exceeds available stock.
- Support user accounts, roles, and permissions.
- Generate revenue and inventory reports.
- Improve the efficiency of daily pharmacy operations.

---

# 👥 System Actors

The system contains three main user groups.

## 👤 Customer

Customers can:

- Browse available products.
- Search for medicines.
- View product information.
- Add products to the shopping cart.
- Review the shopping cart.
- Place online orders.
- Use account-related functions.

---

## 👨‍⚕️ Staff

Pharmacy staff can:

- Search for medicines.
- Search for customers.
- Create pharmacy sales orders.
- Process customer orders.
- Check available inventory.
- Update order information.
- Support daily pharmacy operations.

---

## 🛠 Administrator

Administrators can:

- Manage medicines.
- Manage medicine categories.
- Manage suppliers.
- Manage customers.
- Manage employees.
- Manage user accounts.
- Assign roles and permissions.
- Monitor inventory.
- View reports.
- Manage overall system data.

---

# ✨ Main Features

## 🔐 1. Authentication & Authorization

The system provides authentication and authorization functions including:

- Login
- Logout
- Password change
- Account management
- Account activation/deactivation
- Role-based access control
- User permissions
- BCrypt password hashing

Passwords are stored as hashes instead of plain-text values to improve account security.

---

## 💊 2. Medicine Management

Administrators can manage medicine information in the system.

Medicine information includes:

- Product name
- Selling price
- Product image
- Category
- Supplier
- Active ingredient
- Dosage form
- Prescription / OTC status
- Target users
- Business status

Main operations include:

- Add medicine
- View medicine
- Update medicine
- Search medicine
- Stop selling medicine

---

## 🗂️ 3. Category Management

Medicines are organized into categories to make product management and customer navigation easier.

The system supports:

- Adding categories
- Updating categories
- Viewing categories
- Organizing products by category
- Parent-child category relationships

---

## 📦 4. Inventory & Batch Management

Inventory management is one of the core modules of the project.

The system can store:

- Product ID
- Current quantity
- Batch number
- Expiration date
- Storage / branch information

The system supports:

- Inventory tracking
- Batch management
- Expiration-date tracking
- Inventory updates
- Low-stock monitoring
- Near-expiry medicine monitoring
- Automatic inventory reduction after successful sales

---

## 🏭 5. Supplier Management

The system stores and manages supplier information.

Supplier information includes:

- Supplier name
- Phone number
- Address

Main functions:

- Add supplier
- View supplier
- Update supplier
- Search supplier
- Associate medicines with suppliers

---

## 👤 6. Customer Management

Customer information is stored to support both pharmacy sales and online ordering.

Main functions include:

- Add customer
- View customer
- Update customer
- Search customer by name
- Search customer by phone number
- Use customer information during order processing

---

## 🔎 7. Product Search

The system provides product search functionality to help customers and staff quickly find medicines.

Users can search for medicines based on product information and view relevant product details.

---

## 🛒 8. Online Ordering

Customers can purchase products through the customer-facing website.

### Customer Ordering Flow

```text
Browse Products
      ↓
Search Product
      ↓
View Product
      ↓
Add to Cart
      ↓
Review Cart
      ↓
Place Order
      ↓
Order Created
      ↓
Staff Processes Order
```

The customer website includes:

- Product categories
- Product cards
- Product search
- Promotional banners
- Shopping cart
- Online ordering

---

## 🧾 9. Pharmacy Sales / POS

The system supports direct sales at the pharmacy.

### Pharmacy Sales Flow

```text
Staff Login
      ↓
Select Customer
      ↓
Search Medicine
      ↓
Select Product
      ↓
Enter Quantity
      ↓
Check Inventory
      ↓
Calculate Total
      ↓
Create Order / Invoice
      ↓
Update Inventory
```

The system validates available stock before completing a sale.

If the requested quantity exceeds the available inventory, the system prevents the transaction.

---

## 🎁 10. Promotion / Flash Sale

The customer-facing website includes promotional features.

Examples include:

- Promotional product cards
- Original price
- Discounted price
- Limited-time offers
- Countdown timer
- Quick purchase actions

---

## 👨‍💼 11. Employee Management

Administrators can manage pharmacy employee information.

Employee-related information includes:

- Employee profile
- Position
- Employment status
- Attendance-related information

The system can also display performance information such as:

- Number of processed orders
- Generated revenue

---

## 👥 12. User Account Management

Administrators can manage system accounts.

Main functions include:

- View account list
- Search accounts
- Add account
- Edit account
- Assign roles
- Assign permissions
- Disable account
- Manage account status

---

## 📊 13. Reporting

The system provides reports to support pharmacy management.

Reports include:

- Revenue by period
- Current inventory
- Low-stock medicines
- Medicines approaching expiration

These reports help administrators monitor business operations and inventory status.

---

# 🔄 Business Processes

## 🛒 Customer Purchase Process

```text
Customer
   ↓
Browse / Search Products
   ↓
View Product
   ↓
Add Product to Cart
   ↓
Review Shopping Cart
   ↓
Place Order
   ↓
Order Created
   ↓
Staff Processes Order
```

---

## 🧾 Pharmacy Sales Process

```text
Staff Login
   ↓
Select / Search Customer
   ↓
Search Medicine
   ↓
Enter Quantity
   ↓
Validate Inventory
   ↓
Calculate Total
   ↓
Create Order / Invoice
   ↓
Update Inventory
```

---

## 📦 Inventory Management Process

```text
Medicine
   ↓
Medicine Batch
   ↓
Inventory
   ↓
Quantity & Expiration Tracking
   ↓
Low Stock / Expiration Monitoring
   ↓
Inventory Report
```

---

# 🧩 Functional Requirements

| Functional Group | Main Functions |
|---|---|
| Authentication | Login, logout, password change |
| Medicine Management | Medicine CRUD |
| Category Management | Category CRUD |
| Supplier Management | Supplier CRUD |
| Customer Management | Customer CRUD and search |
| Inventory | Batch, quantity and expiration tracking |
| Sales | Create pharmacy orders / invoices |
| Online Ordering | Cart and online order placement |
| Search | Medicine and customer search |
| Reporting | Revenue and inventory reports |
| Administration | Account, employee and role management |
| Security | Password hashing and access control |

---

# 🗂️ Use Case Overview

The system contains major use cases such as:

- Login
- Manage accounts
- Manage employees
- Manage categories
- Manage suppliers
- Manage products
- Manage inventory
- Search products
- Manage shopping cart
- Create orders
- Process online orders
- Update order status
- Process payments
- Manage promotions
- View reports

---

# 🗃️ Database Design

The system uses **MySQL** as the relational database management system.

Important database tables include:

## `sanpham`

Stores medicine and product information.

Important fields include:

- `masp`
- `tensp`
- `giaban`
- `hinhsp`
- `madv`
- `madm`
- `mancc`
- `madc`
- `makm`
- `requires_rx`
- `dangbaoche`
- `doituong`
- `trangthai`

---

## `danhmuc`

Stores product and medicine categories.

The table supports hierarchical categories using parent-child relationships.

---

## `nhacungcap`

Stores supplier information including:

- Supplier name
- Phone number
- Address

---

## `khachhang`

Stores customer information such as:

- Customer name
- Phone number
- Address

---

## `donhang`

Stores order and invoice information.

Important information includes:

- Order ID
- Customer
- Order date
- Order status
- Order details
- Payment information

---

## `tonkho`

Stores inventory information.

Important information includes:

- Product ID
- Quantity
- Batch number
- Expiration date
- Storage / branch information

---

## `auth`

Stores authentication information including:

- Username
- Password hash
- Employee/customer reference
- Roles
- Permissions

---

## `nhanvien`

Stores employee information including:

- Employee profile
- Position
- Employment status

---

# 🔐 Security

The project applies several basic security mechanisms:

- BCrypt password hashing
- Role-based access control
- User roles
- User permissions
- Session-based authentication
- Account status management

Sensitive passwords are stored as hashes instead of plain-text passwords.

---

# 🛠️ Technology Stack

## Frontend

- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- Fetch / AJAX

## Backend

- PHP

## Database

- MySQL
- phpMyAdmin

## Testing

- Postman
- Functional Testing
- User Acceptance Testing (UAT)

## Development & Project Management Tools

- Visual Studio Code
- Git
- GitHub
- Trello
- Scrum
- Gantt Chart
- Burndown Chart

---

# 🧪 Testing

Testing was performed throughout the development process.

Important test scenarios included:

### Authentication

- Login with valid information
- Login with invalid information
- Authorization based on user roles

### Medicine Management

- Add medicine with valid data
- Add medicine with invalid data
- Update medicine information
- Search medicine

### Supplier Management

- Add supplier
- Update supplier
- View supplier
- Delete/manage supplier information

### Inventory

- Update inventory
- Track batch information
- Track expiration dates
- Prevent sales above available stock

### Pharmacy Sales

- Search customer
- Search medicine
- Add medicine to order
- Calculate order total
- Create order
- Automatically reduce inventory

### Reporting

- Revenue report validation
- Inventory report validation
- Low-stock report validation
- Expiration report validation

### UAT

User Acceptance Testing was performed before final project handover to validate the complete system workflow.

---

# 🏃 Project Management

The project was organized using **Scrum** and divided into **5 Sprints**.

| Sprint | Main Focus |
|---|---|
| Sprint 1 | Project initialization, authentication, basic UI and database |
| Sprint 2 | Medicine, inventory and batch management |
| Sprint 3 | Pharmacy sales and medicine search |
| Sprint 4 | Online ordering, reporting and user management |
| Sprint 5 | UAT, bug fixing and project handover |

---

## 📋 Trello Workflow

The team used Trello to organize project tasks.

```text
Backlog
   ↓
To Do
   ↓
In Progress
   ↓
Review
   ↓
Done
```

The project also applied:

- Sprint Planning
- Daily Scrum
- Sprint Review
- Sprint Retrospective
- Gantt Chart
- Burndown Chart

---

# 👩‍💻 My Contribution — Chau Thi Bich Ngoc

During the project, I participated in **business analysis, UI/UX design, development, project coordination, and functional testing**.

## 📋 Business Analysis & Documentation

My responsibilities included:

- Participating in requirement analysis for pharmacy workflows.
- Analyzing customer data requirements.
- Analyzing invoice-detail data.
- Analyzing account and user-role requirements.
- Supporting functional analysis.
- Supporting business-flow definition.
- Participating in database discussions.
- Supporting project documentation.
- Tracking project progress and assigned tasks.

---

## 🎨 UI/UX & Functional Design

I participated in designing and refining several system interfaces, including:

- Basic UI/UX framework
- Inventory and batch management interface
- Pharmacy POS / invoice interface
- Online ordering interface
- Shopping cart interface

This required translating functional requirements into application screens and user flows.

---

## 💻 Development

I participated in the implementation of several system functions, including:

- Medicine/category CRUD
- Customer CRUD
- Inventory-related functionality
- User-management functionality
- Customer-facing ordering functionality

---

## 🧪 Testing

My testing activities included:

- Authentication and authorization API testing with Postman
- Medicine creation testing
- Inventory update testing
- Pharmacy sales workflow testing
- Reporting functionality testing
- User Acceptance Testing
- Bug fixing support
- Reviewing system behavior before handover

---

## 🔄 My Project Workflow

Through the project, I experienced the software development process from:

```text
Requirement Analysis
        ↓
Business Flow
        ↓
Database / Functional Design
        ↓
UI/UX Design
        ↓
Implementation
        ↓
Functional Testing
        ↓
UAT
        ↓
Project Handover
```

This experience strengthened my interest in **Business Analysis and Product Development**, especially the process of translating user and business requirements into clear system functionality.

---

# 📈 Project Results

At the end of the project:

- Authentication and role management were completed.
- Medicine management was completed.
- Supplier management was completed.
- Inventory tracking by batch and expiration date was implemented.
- Pharmacy sales were implemented.
- Automatic inventory deduction was implemented.
- Product search was completed.
- Shopping cart functionality was completed.
- Online ordering was completed.
- User management was completed.
- Revenue reporting was completed.
- Inventory reporting was completed.
- UAT issues were reviewed and fixed.
- The interface was refined.
- The system was prepared for demonstration and handover.

---

# 🎥 Demo

You can watch the complete project demonstration here:

### ▶️ [Watch An Tam Pharmacy Management System Demo](https://drive.google.com/file/d/1gGH47GNhcV-GzOefzQJNRYuuEMjcGxFb/view)

---

# 📚 What I Learned

Through this project, I gained practical experience in:

- Requirement Analysis
- Business Process Analysis
- Functional Requirements
- Use Case Analysis
- User Flow Analysis
- Database Modeling
- UI/UX Collaboration
- Functional Testing
- API Testing with Postman
- User Acceptance Testing
- Scrum
- Git/GitHub
- Project Documentation
- Team Collaboration

Most importantly, the project helped me understand the relationship between:

```text
User Needs
    ↓
Business Requirements
    ↓
Functional Requirements
    ↓
UI / User Flow
    ↓
Technical Implementation
    ↓
Testing
    ↓
Working Product
```

This project strengthened my interest in pursuing a career in **Business Analysis / Product Development**.


---

> This project was developed for academic purposes as part of the Information Technology Project Management course at UTH.
