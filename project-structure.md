# Cooperative Society Management System - Project Structure

```
cooperative-society/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── MemberController.php
│   │   │   │   ├── SavingsController.php
│   │   │   │   ├── LoanController.php
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── DocumentController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   └── MeetingController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   ├── LogActivity.php
│   │   │   └── SanctumMiddleware.php
│   │   └── Requests/
│   │       ├── MemberRequest.php
│   │       ├── LoanRequest.php
│   │       ├── SavingsRequest.php
│   │       └── ApprovalRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Member.php
│   │   ├── Savings.php
│   │   ├── Loan.php
│   │   ├── LoanRepayment.php
│   │   ├── Transaction.php
│   │   ├── Document.php
│   │   ├── Meeting.php
│   │   ├── Notification.php
│   │   ├── ActivityLog.php
│   │   └── Role.php
│   ├── Services/
│   │   ├── LoanService.php
│   │   ├── SavingsService.php
│   │   ├── NotificationService.php
│   │   ├── ReportService.php
│   │   └── AuditService.php
│   └── Jobs/
│       ├── SendLoanApprovalEmail.php
│       ├── SendRepaymentReminder.php
│       ├── GenerateMonthlyReport.php
│       └── FlagInactiveMembers.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_roles_table.php
│   │   ├── create_members_table.php
│   │   ├── create_savings_table.php
│   │   ├── create_loans_table.php
│   │   ├── create_loan_repayments_table.php
│   │   ├── create_transactions_table.php
│   │   ├── create_documents_table.php
│   │   ├── create_meetings_table.php
│   │   ├── create_notifications_table.php
│   │   ├── create_activity_logs_table.php
│   │   ├── create_meeting_attendance_table.php
│   │   └── create_votes_table.php
│   └── seeders/
│       ├── UserSeeder.php
│       ├── MemberSeeder.php
│       └── SettingsSeeder.php
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   ├── admin.blade.php
│   │   │   └── member.blade.php
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── member/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── profile.blade.php
│   │   │   ├── savings.blade.php
│   │   │   ├── loans.blade.php
│   │   │   └── documents.blade.php
│   │   ├── admin/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── members.blade.php
│   │   │   ├── loans.blade.php
│   │   │   ├── savings.blade.php
│   │   │   ├── reports.blade.php
│   │   │   └── settings.blade.php
│   │   └── components/
│   │       ├── navbar.blade.php
│   │       ├── sidebar.blade.php
│   │       └── charts.blade.php
│   └── css/
│       └── app.css
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── public/
│   ├── storage/
│   │   ├── documents/
│   │   ├── receipts/
│   │   └── member_ids/
│   └── images/
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── LoanTest.php
│   │   └── SavingsTest.php
│   └── Unit/
│       ├── LoanServiceTest.php
│       └── ReportServiceTest.php
├── config/
│   ├── cooperative.php
│   └── roles.php
└── docs/
    ├── database-schema.md
    ├── api-documentation.md
    ├── installation-guide.md
    └── er-diagram.png
```

## API Routes Structure

```
POST   /api/auth/register          - Member registration
POST   /api/auth/login             - User login
POST   /api/auth/logout            - User logout

GET    /api/member/dashboard       - Member dashboard data
GET    /api/member/profile         - Member profile
PUT    /api/member/profile         - Update member profile
GET    /api/member/savings         - Member savings history
GET    /api/member/loans           - Member loan history
GET    /api/member/transactions    - Member transactions

POST   /api/savings/contribute     - Add savings contribution
GET    /api/savings/wallet         - Wallet balance
GET    /api/savings/history        - Savings history
GET    /api/savings/receipt/{id}   - Download receipt

POST   /api/loans/apply            - Apply for loan
GET    /api/loans/status/{id}      - Check loan status
GET    /api/loans/schedule/{id}    - Get repayment schedule
POST   /api/loans/repay/{id}       - Make loan repayment

GET    /api/admin/dashboard        - Admin dashboard
GET    /api/admin/members          - List all members
PUT    /api/admin/members/{id}     - Update member status
GET    /api/admin/loans            - List all loans
PUT    /api/admin/loans/{id}/approve - Approve/reject loan
GET    /api/admin/savings          - All savings records
PUT    /api/admin/savings/{id}     - Correct savings entry
GET    /api/admin/reports          - Financial reports
GET    /api/admin/activity-log     - System activity log

POST   /api/documents/upload       - Upload document
GET    /api/documents              - List documents
DELETE /api/documents/{id}         - Delete document

GET    /api/notifications          - Get notifications
PUT    /api/notifications/{id}/read - Mark as read

POST   /api/meetings               - Create meeting
GET    /api/meetings               - List meetings
POST   /api/meetings/{id}/attend   - Mark attendance
POST   /api/meetings/{id}/vote     - Cast vote
```

## Technology Stack

- **Backend**: Laravel 10.x
- **Frontend**: Blade Templates + TailwindCSS 3.x
- **Database**: MySQL 8.x
- **Authentication**: Laravel Sanctum
- **API Documentation**: OpenAPI/Swagger
- **PDF Generation**: DomPDF
- **Excel Export**: Laravel Excel
- **Notifications**: Mail + SMS (Twilio)
- **Task Scheduling**: Laravel Scheduler
- **File Storage**: Laravel Storage (Local/S3)