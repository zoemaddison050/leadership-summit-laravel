# Admin Panel Access Guide

## How to Access Your Admin Panel

### 1. Admin Login Steps

**IMPORTANT**: Admin login is separate from the public website!

1. **Go to the admin login page**: `http://localhost:8000/login`
2. **Login with these credentials**:
   - **Email**: `admin@leadershipsummit.com`
   - **Password**: `password`
3. **After successful login, access admin panel**: `http://localhost:8000/admin`

**Note**:

- The login page is for **admin access only** - no public user registration
- Regular visitors use the "Register Now" buttons for **event registration**
- Direct access to `/admin` without logging in first will redirect you to the login page

### 2. Admin Panel Features

Once logged in, you have access to the following management areas:

#### 🎯 **Event Management** (`/admin/events`)

- Create, edit, and delete events
- Set default event (appears on homepage)
- Manage event details:
  - Title, description, dates
  - Location and venue information
  - Ticket pricing and availability
  - Registration deadlines
  - Event status (active/inactive)

#### 🎤 **Speaker Management** (`/admin/speakers`)

- Add new speakers
- Edit speaker profiles
- Upload speaker photos
- Manage speaker information:
  - Name, title, company
  - Biography and expertise
  - Contact information
  - Social media links

#### 💰 **Cryptocurrency Wallet Settings** (`/admin/wallet-settings`)

- Configure crypto payment wallets
- Supported cryptocurrencies:
  - Bitcoin (BTC)
  - Ethereum (ETH)
  - USDT (ERC-20)
- Manage wallet addresses
- Enable/disable specific cryptocurrencies
- Real-time price integration with CoinGecko API

#### 💳 **Payment Management** (`/admin/payments/pending`)

- Review pending cryptocurrency payments
- Approve or decline payments
- Track payment status
- View payment details and transaction information

#### 📊 **Dashboard** (`/admin`)

- Overview of system statistics
- Recent registrations
- Payment summaries
- Quick access to all management areas

### 3. Quick Setup Commands

If you need to create the admin user or reset the database:

```bash
# Create admin user (if not exists)
docker-compose exec app php artisan db:seed --class=UserSeeder

# Reset and seed entire database
docker-compose exec app php artisan migrate:fresh --seed

# Clear application cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### 4. Admin Panel Navigation

The admin panel includes:

- **Sidebar Navigation**: Quick access to all management areas
- **Responsive Design**: Works on desktop and mobile
- **Role-based Access**: Only admin users can access these features
- **Secure Authentication**: Protected by Laravel's authentication system

### 5. Key Admin Functions

#### Setting Default Event

1. Go to **Event Management**
2. Click "Set as Default" next to your main event
3. This event will appear on the homepage registration buttons

#### Managing Crypto Payments

1. Go to **Wallet Settings**
2. Add wallet addresses for each cryptocurrency
3. Enable/disable specific payment methods
4. Monitor payments in **Payment Management**

#### Speaker Management

1. Go to **Speaker Management**
2. Add speaker details and upload photos
3. Speakers automatically appear on the speakers page

### 6. Security Notes

- Change the default admin password after first login
- Admin access is restricted to users with 'admin' role
- All admin actions are logged for security
- Use HTTPS in production for secure admin access

### 7. Troubleshooting Admin Access

If you can't access the admin panel:

```bash
# Check if admin user exists
docker-compose exec app php artisan tinker --execute="User::where('email', 'admin@leadershipsummit.com')->first()"

# Create admin user manually
docker-compose exec app php artisan tinker --execute="
\$adminRole = App\Models\Role::where('name', 'admin')->first();
\$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@leadershipsummit.com',
    'password' => bcrypt('password')
]);
\$user->roles()->attach(\$adminRole);
echo 'Admin user created successfully';
"

# Clear authentication cache
docker-compose exec app php artisan auth:clear-resets
```

## Production Deployment Ready

Your application is now production-ready with:

- ✅ All test files removed
- ✅ Comprehensive admin panel
- ✅ Cryptocurrency payment system
- ✅ Event and speaker management
- ✅ Secure authentication system
- ✅ Optimized deployment scripts

To deploy to production, run:

```bash
./deploy.sh
```
