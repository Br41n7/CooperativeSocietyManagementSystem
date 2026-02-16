# Cooperative Society Management System - API Documentation

## Base URL

```
Production: https://api.cooperative.com/api
Development: http://localhost:8000/api
```

## Authentication

All API endpoints (except public endpoints) require authentication using Bearer tokens.

### Headers

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Login

```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "member",
      "permissions": ["profile.view", "savings.view", "loans.apply"]
    },
    "token": "1|abc123xyz..."
  }
}
```

### Logout

```http
POST /api/auth/logout
```

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Member Endpoints

### Get Member Dashboard

```http
GET /api/member/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "member": {
      "id": 1,
      "member_number": "MEM000001",
      "first_name": "John",
      "last_name": "Doe",
      "status": "active",
      "total_savings": 50000.00,
      "credit_score": 95
    },
    "savings_balance": 50000.00,
    "outstanding_loan_balance": 0.00,
    "loan_eligibility": 150000.00,
    "credit_score": 95,
    "default_risk": "low",
    "active_loan": null,
    "next_payment": null,
    "recent_savings": [...],
    "recent_loans": [...],
    "recent_transactions": [...],
    "unread_notifications": 3
  }
}
```

### Get Member Profile

```http
GET /api/member/profile
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "member_number": "MEM000001",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+2348012345678",
    "address": "123 Main Street",
    "city": "Lagos",
    "state": "Lagos",
    "status": "active",
    "membership_date": "2020-01-01",
    "total_savings": 50000.00,
    "credit_score": 95
  }
}
```

### Update Member Profile

```http
PUT /api/member/profile
```

**Request Body:**
```json
{
  "phone": "+2348012345678",
  "address": "123 New Street",
  "city": "Lagos",
  "state": "Lagos",
  "occupation": "Engineer",
  "monthly_income": 300000.00
}
```

---

## Savings Endpoints

### Make Contribution

```http
POST /api/savings/contribute
```

**Request Body:**
```json
{
  "amount": 10000.00,
  "contribution_type": "monthly",
  "payment_method": "bank_transfer",
  "payment_date": "2024-01-15",
  "notes": "Monthly savings contribution"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Contribution recorded successfully",
  "data": {
    "savings": {
      "id": 1,
      "transaction_number": "SAV202401150001",
      "amount": 10000.00,
      "contribution_type": "monthly",
      "payment_date": "2024-01-15"
    },
    "transaction": {
      "id": 1,
      "transaction_number": "TXN202401150001",
      "amount": 10000.00
    },
    "new_balance": 60000.00
  }
}
```

### Get Wallet Balance

```http
GET /api/savings/wallet
```

**Response:**
```json
{
  "success": true,
  "data": {
    "balance": 50000.00,
    "total_contributions": 12,
    "total_amount": 50000.00,
    "monthly_contributions": [...],
    "recent_savings": [...]
  }
}
```

### Get Savings History

```http
GET /api/savings/history?page=1&per_page=20&contribution_type=monthly&from_date=2024-01-01&to_date=2024-12-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "amount": 10000.00,
        "contribution_type": "monthly",
        "payment_date": "2024-01-15",
        "transaction_number": "SAV202401150001"
      }
    ],
    "total": 12,
    "per_page": 20
  }
}
```

---

## Loan Endpoints

### Apply for Loan

```http
POST /api/loans/apply
```

**Request Body:**
```json
{
  "amount": 150000.00,
  "purpose": "Business expansion",
  "repayment_period": 12,
  "repayment_frequency": "monthly",
  "interest_type": "flat",
  "collateral": "Vehicle",
  "guarantor_name": "Jane Smith",
  "guarantor_phone": "+2348098765432",
  "guarantor_address": "456 Oak Avenue",
  "guarantor_member_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Loan application submitted successfully",
  "data": {
    "loan": {
      "id": 1,
      "loan_number": "LN202401150001",
      "amount": 150000.00,
      "interest_rate": 15.00,
      "total_interest": 22500.00,
      "total_repayment": 172500.00,
      "monthly_repayment": 14375.00,
      "status": "pending",
      "start_date": "2024-01-22",
      "end_date": "2025-01-22"
    },
    "repayment_schedule": [...]
  }
}
```

### Get Loan Details

```http
GET /api/loans/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "loan": {
      "id": 1,
      "loan_number": "LN202401150001",
      "amount": 150000.00,
      "total_repayment": 172500.00,
      "status": "active"
    },
    "repayment_schedule": [
      {
        "id": 1,
        "installment_number": 1,
        "due_date": "2024-02-22",
        "due_amount": 14375.00,
        "principal_amount": 12500.00,
        "interest_amount": 1875.00,
        "status": "pending"
      }
    ],
    "total_repaid": 28750.00,
    "outstanding_balance": 143750.00,
    "completion_percentage": 16.67
  }
}
```

### Make Loan Repayment

```http
POST /api/loans/{id}/repay
```

**Request Body:**
```json
{
  "repayment_id": 1,
  "amount": 14375.00,
  "payment_method": "bank_transfer",
  "payment_date": "2024-02-22"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment recorded successfully",
  "data": {
    "repayment": {
      "id": 1,
      "paid_amount": 14375.00,
      "status": "paid"
    },
    "outstanding_balance": 129375.00
  }
}
```

---

## Admin Endpoints

### Get Admin Dashboard

```http
GET /api/admin/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "members": {
      "total": 100,
      "pending": 5,
      "defaulting": 2
    },
    "savings": {
      "total": 5000000.00,
      "this_month": 500000.00
    },
    "loans": {
      "total": 25,
      "active_amount": 2500000.00,
      "pending": 3,
      "outstanding": 1750000.00
    },
    "financial_summary": {
      "total_assets": 7500000.00,
      "total_liabilities": 1750000.00,
      "net_worth": 5750000.00
    }
  }
}
```

### Approve Member

```http
PUT /api/admin/members/{id}/approve
```

**Response:**
```json
{
  "success": true,
  "message": "Member approved successfully",
  "data": {
    "id": 1,
    "status": "active",
    "membership_date": "2024-01-15"
  }
}
```

### Approve Loan

```http
PUT /api/admin/loans/{id}/approve
```

**Response:**
```json
{
  "success": true,
  "message": "Loan approved successfully",
  "data": {
    "id": 1,
    "status": "secretary_approved",
    "secretary_approved_at": "2024-01-15T10:30:00Z"
  }
}
```

---

## Report Endpoints

### Financial Summary

```http
GET /api/reports/financial?from_date=2024-01-01&to_date=2024-12-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "from": "2024-01-01",
      "to": "2024-12-31"
    },
    "income": {
      "savings_in": 6000000.00,
      "loan_repayments": 3000000.00,
      "interest_earned": 450000.00,
      "penalties": 50000.00,
      "total": 9500000.00
    },
    "expenses": {
      "savings_out": 1000000.00,
      "loan_disbursements": 4000000.00,
      "total": 5000000.00
    },
    "net_income": 4500000.00
  }
}
```

### Member Report

```http
GET /api/reports/members?status=active
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_members": 100,
      "total_savings": 5000000.00,
      "total_loans_taken": 4000000.00,
      "outstanding_loans": 1750000.00,
      "average_credit_score": 87.5
    },
    "by_status": {
      "active": {
        "count": 95,
        "total_savings": 4750000.00,
        "total_loans": 3800000.00
      }
    },
    "top_savers": [...],
    "top_borrowers": [...]
  }
}
```

---

## Error Responses

All endpoints return errors in the following format:

```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field": ["Error details"]
  }
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

---

## Rate Limiting

API requests are limited to:
- 100 requests per minute per IP address
- 1000 requests per hour per user

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1609459200
```

---

## Pagination

Paginated endpoints use standard pagination:

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)

**Response:**
```json
{
  "data": [...],
  "current_page": 1,
  "total": 100,
  "per_page": 20,
  "last_page": 5
}
```

---

## Webhooks

Configure webhook URLs to receive notifications:

```http
POST /api/webhooks
```

**Request Body:**
```json
{
  "url": "https://yourdomain.com/webhook",
  "events": ["loan.approved", "repayment.due"]
}
```

---

## SDK Examples

### JavaScript (Fetch)

```javascript
const login = async (email, password) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password })
  });
  return response.json();
};

const getDashboard = async (token) => {
  const response = await fetch('/api/member/dashboard', {
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });
  return response.json();
};
```

### Python (Requests)

```python
import requests

def login(email, password):
    response = requests.post('/api/auth/login', json={
        'email': email,
        'password': password
    })
    return response.json()

def get_dashboard(token):
    headers = {'Authorization': f'Bearer {token}'}
    response = requests.get('/api/member/dashboard', headers=headers)
    return response.json()
```

---

## Testing

Use the provided demo accounts to test the API:

```bash
# Login as admin
curl -X POST https://api.cooperative.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@cooperative.com","password":"admin123"}'

# Get dashboard (replace TOKEN)
curl -X GET https://api.cooperative.com/api/admin/dashboard \
  -H "Authorization: Bearer TOKEN"
```

---

## Support

For API support:
- Email: api-support@cooperative.com
- Documentation: https://docs.cooperative.com/api
- Status: https://status.cooperative.com