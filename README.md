# SOUND Group - Entertainment Portal (PHP + MySQL)

An entertainment portal for **SOUND Group** designed according to project specifications. Hosts new and old Videos and Songs in both **REGIONAL** and **ENGLISH** languages.

---

## 📋 Features implemented against requirements:

### 1. General & Navigation
- Proper menu structure: Home, Music, Videos, Albums, Artists, Search, and Admin Panel.
- Arranged by: **ALBUM**, **ARTIST**, **YEAR**, **GENRE**, and **LANGUAGE**.
- Home page with site intro, statistics, and **latest 5 Music + 5 Video** listing.
- **Flashing "NEW" icon/badge** alongside new additions.

### 2. User Roles:
- **Administrator**:
  - Add & Delete Music files with info (Cover, Audio, Lyrics/Description, Year, Genre, Language).
  - Add & Delete Video files with info.
  - Create Categories: **YEAR**, **ARTIST**, **ALBUM**, **GENRE**, **LANGUAGE**.
  - Create and Manage Users / Logins (Active / Inactive status, Role assignment).
  - Manage information/details on the website.
  - Moderate & Approve/Reject Reviews.
- **Normal USER**:
  - Register account with unique **USERID**.
  - Mandatory field validation: **Name, Address, Phone Number, Email-id**.
  - Search for Music/Video based on **Name, Artist, Year, Album**.
  - **ADD / Modify Reviews** and **ADD / Modify Ratings** (1 to 5 Stars).
  - Profile management.

---

## 🚀 Setup & Run Instructions (XAMPP / WAMP):

### Step 1: Copy Project
Copy or move the `sound-php` folder into your XAMPP `htdocs` directory:
`C:\xampp\htdocs\sound-php`

### Step 2: Start Services
Open **XAMPP Control Panel** and start:
- **Apache**
- **MySQL**

### Step 3: Import Database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Create a database named `sound_db` (or simply import the file directly).
3. Click on the **Import** tab.
4. Choose the file `database.sql` located inside the project folder:
   `sound-php/database.sql`
5. Click **Go / Import**.

### Step 4: Run the Application
Open your browser and visit:
- **Website**: `http://localhost/sound-php/`
- **Admin Panel**: `http://localhost/sound-php/admin/`

---

## 🔑 Default Login Credentials:

### 1. Administrator:
- **Email**: `admin@soundgroup.com`
- **Password**: `password` (or `Admin@123`)

### 2. Demo User:
- **Email**: `rahul@gmail.com`
- **Password**: `password` (or `User@123`)
- **UserID**: `rahul_99`

