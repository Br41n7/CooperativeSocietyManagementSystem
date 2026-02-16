# Cooperative Society Management System

A comprehensive web-based cooperative society management system built with Laravel 10, MySQL, and TailwindCSS. This system provides full functionality for managing members, savings, loans, meetings, documents, and financial reporting with role-based access control.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![Laravel](https://img.shields.io/badge/Laravel-10.0-red.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

##  Features

### Member Management
- Online member registration with admin approval workflow
- Unique auto-generated member IDs (MEMYYYYMMXXXXXX format)
- Complete member profiles with personal and financial information
- Membership status management (active, inactive, suspended, defaulting)
- Credit score tracking and automatic updates
- Member dashboard with personalized information

### Savings & Contributions
- Multiple contribution types (monthly, voluntary, fixed, penalty, refund)
- Flexible payment methods (cash, bank transfer, online, deduction)
- Real-time wallet balance updates
- Complete transaction history
- Auto-generated receipts
- Admin adjustment capabilities with audit logging
- Monthly contribution tracking

### Loan Management
- Multi-level approval workflow (Secretary → Chairman → Treasurer)
- Flexible interest calculation (flat, reducing, compound)
- Automatic repayment schedule generation
- Outstanding balance tracking
- Loan status management throughout lifecycle
- Payment reminders and overdue tracking
- Savings deduction option for repayments
- Guarantor tracking

### Admin Panel
- Role-based access control with granular permissions
- Super Admin, Chairman, Secretary, Treasurer, Member roles
- Member approval workflow
- Loan approval/rejection system
- Savings management and corrections
- Comprehensive dashboard with analytics
- Activity logging for audit trails

### Accounting & Reports
- ✅ Real-time financial summaries
- ✅ Income vs expense tracking
- ✅ Loan repayment analytics
- ✅ Member reports with statistics
- ✅ Savings reports by type and period
- ✅ Monthly financial summaries
- ✅ Export capabilities (PDF/Excel ready)

### Notifications
-  In-app notification system
-  Email notifications (configurable)
-  SMS support (Twilio integration)
-  Loan approval/rejection alerts
-  Contribution confirmations
-  Repayment reminders
-  Meeting notices

### Document Management
-  Document upload and storage
-  Multiple document types (constitution, minutes, agreements, etc.)
-  Public/private document access
-  Member ID generation
-  Download tracking

### Meetings & Voting
- Meeting creation and management
- Attendance tracking
- Online voting/polls
- Multiple vote types (yes/no, multiple choice, open)
- Real-time vote results

### Smart Features
-  Loan default risk prediction
-  Automatic loan limit calculation based on history
-  Credit scoring algorithm
-  Spending pattern analytics
-  Inactive member flagging

### Security
-  Secure authentication with Laravel Sanctum
-  Encrypted passwords
-  Role-based authorization
-  Complete activity audit logs
-  Session protection
-  CSRF protection

### Mobile Friendly
-  Fully responsive design
-  TailwindCSS for modern styling
-  Touch-optimized interfaces
-  Mobile dashboard views

##  System Architecture

### Technology Stack

**Backend:**
- PHP 8.1+
- Laravel 10.x Framework
- MySQL 8.0+
- Composer

**Frontend:**
- Blade Templates
- TailwindCSS 3.x
- Vanilla JavaScript
- Font Awesome Icons

**API & Security:**
- RESTful API design
- Laravel Sanctum for authentication
- JWT Token-based auth
- Role-based access control

**Database:**
- 13 optimized tables
- Proper indexing
- Foreign key constraints
- Audit trail support

### Database Schema

The system uses 13 interconnected tables:

1. **users** - Authentication and user management
2. **members** - Member profiles and information
3. **roles** - Role definitions with permissions
4. **savings** - Savings and contribution records
5. **loans** - Loan applications and details
6. **loan_repayments** - Repayment schedules
7. **transactions** - All financial transactions
8. **documents** - Document storage
9. **meetings** - Meeting records
10. **meeting_attendance** - Attendance tracking
11. **votes** - Voting and polls
12. **vote_responses** - Vote records
13. **notifications** - User notifications
14. **activity_logs** - System audit trail

See [database-schema.md](database-schema.md) for complete schema details.

## 🚀 Quick Start

### Prerequisites

- PHP 8.1 or higher
- Composer 2.0+
- MySQL 8.0+
- Node.js 18.x+
- Web server (Nginx/Apache)

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/cooperative-society.git
cd cooperative-society

# Install dependencies
composer install
npm install
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials
# DB_DATABASE=cooperative_society
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 755 storage bootstrap/cache
```

### Access the Application

- **Member Dashboard**: http://localhost:8000/dashboard
- **Admin Dashboard**: http://localhost:8000/admin/dashboard
- **API Documentation**: See [docs/api-documentation.md](docs/api-documentation.md)

### Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@cooperative.com | admin123 |
| Chairman | chairman@cooperative.com | chairman123 |
| Secretary | secretary@cooperative.com | secretary123 |
| Treasurer | treasurer@cooperative.com | treasurer123 |
| Member | member4@cooperative.com | member123 |

**Important**: Change all default passwords immediately after installation!

##  Project Structure

```
cooperative-society/
├── app/
│   ├── Http/Controllers/Api/    # API Controllers
│   ├── Models/                   # Eloquent Models
│   ├── Services/                 # Business Logic
│   └── Jobs/                     # Background Jobs
├── database/
│   ├── migrations/               # Database Migrations
│   └── seeders/                  # Seed Data
├── resources/
│   └── views/                    # Blade Templates
├── routes/
│   └── api.php                   # API Routes
├── docs/                         # Documentation
└── public/                       # Public Assets
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new member
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Member
- `GET /api/member/dashboard` - Member dashboard
- `GET /api/member/profile` - Get profile
- `PUT /api/member/profile` - Update profile

### Savings
- `POST /api/savings/contribute` - Make contribution
- `GET /api/savings/wallet` - Get wallet balance
- `GET /api/savings/history` - Savings history

### Loans
- `POST /api/loans/apply` - Apply for loan
- `GET /api/loans` - List loans
- `GET /api/loans/{id}` - Get loan details
- `POST /api/loans/{id}/repay` - Make repayment

### Admin
- `GET /api/admin/dashboard` - Admin dashboard
- `GET /api/admin/members` - List all members
- `PUT /api/admin/members/{id}/approve` - Approve member
- `PUT /api/admin/loans/{id}/approve` - Approve loan

### Reports
- `GET /api/reports/financial` - Financial summary
- `GET /api/reports/members` - Member report
- `GET /api/reports/loans` - Loan report

See [docs/api-documentation.md](docs/api-documentation.md) for complete API documentation.

##  User Roles & Permissions

### Super Admin
- Full system access
- User management
- System configuration

### Chairman
- Approve loans
- Approve members
- View reports
- Manage meetings

### Secretary
- Approve loans (first level)
- Manage documents
- Manage meetings
- View reports

### Treasurer
- Manage savings
- Approve loans (final level)
- Disburse loans
- Financial reports

### Member
- View own profile
- Make contributions
- Apply for loans
- View personal data

##  Security Features

- **Authentication**: Laravel Sanctum with token-based auth
- **Authorization**: Role-based access control
- **Encryption**: Passwords hashed with bcrypt
- **CSRF Protection**: Built-in CSRF tokens
- **SQL Injection**: ORM-based queries prevent injection
- **XSS Protection**: Input sanitization and output escaping
- **Audit Logging**: Complete activity tracking
- **Session Management**: Secure session handling

##  Reports & Analytics

### Available Reports
- Financial Summary
- Member Statistics
- Loan Performance
- Savings Analysis
- Repayment Tracking
- Activity Logs
- Monthly Trends
- Risk Assessment

### Export Formats
- PDF Reports (DomPDF)
- Excel Spreadsheets (Laravel Excel)
- CSV Export

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

##  Documentation

- [Installation Guide](docs/installation-guide.md) - Complete setup instructions
- [API Documentation](docs/api-documentation.md) - Full API reference
- [Database Schema](database-schema.md) - Database structure
- [Project Structure](project-structure.md) - File organization

##  Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

##  License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

##  Acknowledgments

- Laravel Framework
- TailwindCSS
- Font Awesome
- All contributors

##  Support

For support, email olaleganiyanu1@gmail.com or open an issue in the GitHub repository.

##  Roadmap

- [ ] Mobile App (React Native)
- [ ] Advanced Analytics Dashboard
- [ ] AI-powered Loan Scoring
- [ ] Multi-language Support
- [ ] Advanced Reporting with Charts
- [ ] Integration with Payment Gateways
- [ ] Blockchain for Transaction Records
- [ ] Mobile Push Notifications
- [ ] Video Meeting Integration
- [ ] Advanced Document Management

---

**Built with ❤ for Cooperative Societies**