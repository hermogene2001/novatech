# Novatech Investment Platform

A comprehensive investment platform built with PHP and MySQL that allows users to purchase investment products and receive daily earnings.

## Features Added

1. **Automated Daily Earnings Calculation** - Cron job system for automatic daily earning distributions
2. **Email Notifications** - Transaction and account activity notifications
3. **Agent Dashboard** - Interface for agents to process recharges and withdrawals
4. **Mobile API** - RESTful API for mobile app integration
5. **Two-Factor Authentication** - Enhanced security with 2FA
6. **Data Visualization** - Investment performance charts and reports
7. **Referral Tracking** - Multi-level referral system with commission calculations

## Features

### User Roles
- **Client**: Can register, login, purchase products, view investments, and withdraw earnings
- **Agent**: Can assist clients with recharges and withdrawals
- **Admin**: Can manage users, products, and platform settings

### Core Functionality
- User registration and authentication
- Investment product management
- Daily earning calculations
- Transaction tracking
- Referral system
- Withdrawal requests
- Bank account management

## Database Structure

The platform uses the following key tables:

- `users`: Stores user information with roles (client, admin, agent)
- `products`: Investment products with pricing and earning details
- `purchases`: Records of user product purchases
- `transactions`: All financial transactions (deposits, withdrawals, earnings)
- `investments`: Active investments with daily profit tracking
- `referrals`: User referral relationships
- `withdrawals`: Withdrawal requests and processing
- `user_banks`: Bank account information for users

## Installation

1. Clone the repository to your web server directory
2. Import the `kinginvest.sql` file to create the database structure
3. Update the database credentials in `config/database.php`
4. Ensure the `uploads` directory is writable

## Directory Structure

```
novatech/
├── admin/              # Admin panel files
├── agent/              # Agent dashboard files
├── api/                # REST API for mobile apps
├── assets/             # CSS, JavaScript, and other assets
│   ├── css/
│   └── js/
├── client/             # Client dashboard files
├── config/             # Configuration files
├── cron/               # Cron jobs for automated tasks
├── lib/                # Library files
├── migrations/         # Database migration scripts
├── uploads/            # Upload directories
│   ├── products/
│   ├── slides/
│   └── profiles/
├── index.php           # Homepage
├── login.php           # User login page
├── login_2fa.php       # 2FA verification page
├── register.php        # User registration page
├── kinginvest.sql      # Database schema
└── init_db.php         # Database initialization script
```

## Getting Started

1. Visit the homepage to register or login
2. Clients can browse and purchase investment products
3. Daily earnings are automatically calculated and added to user balances
4. Users can request withdrawals through their dashboard

## Security Features

- Password hashing using PHP's `password_hash()` function
- Session management for authentication
- Prepared statements to prevent SQL injection
- Role-based access control

## Future Enhancements

- Enhanced admin panel with detailed reporting
- Mobile-responsive design improvements
- Multi-language support
- Advanced analytics and reporting dashboards
- Mobile app development
- Additional security features