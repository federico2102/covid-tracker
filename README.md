---
# COVID-19 Tracker Application

This Laravel-based web application helps manage check-ins, track COVID-19 exposures, and notify users who may have been in contact with infected individuals. It features real-time integration with Google Maps API to display locations and their details.

## Key Features

- **User Roles**: Regular users and admins, with unique functionalities for each.
- **Check-In System**: Users can check in to specific locations via QR codes, with automatic check-out after 3 hours.
- **Infection Reports**: Users can report positive or negative COVID-19 tests. Notifications are sent to users who were in contact with an infected person.
- **Contact Notifications**: Users who have been in contact with an infected individual are notified via email and app.
- **Auto Reset**: Infected and contacted statuses are automatically reset after 14 days.
- **Google Maps API Integration**: Locations include detailed information such as name, address, and geolocation, all displayed on a map.

## Installation

To set up the project locally, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/covid-tracker.git
   cd covid-tracker
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Set up environment variables**:
   - Copy `.env.example` to `.env`:
     ```bash
     cp .env.example .env
     ```
   - Update `.env` with your database credentials, mail configuration, and Google Maps API key:
     ```env
     DB_DATABASE=your_database
     DB_USERNAME=your_username
     DB_PASSWORD=your_password

     GOOGLE_MAPS_API_KEY=your_google_maps_api_key
     ```

4. **Generate an application key**:
   ```bash
   php artisan key:generate
   ```

5. **Run migrations**:
   ```bash
   php artisan migrate
   ```

6. **Run the project**:
   ```bash
   php artisan serve
   ```

7. **Set up front-end dependencies** (optional but recommended):
   ```bash
   npm run dev
   ```

## Usage

### Admin Features

- **Add Locations**: Admins can add new locations with details such as name, address, geolocation, maximum capacity, and optional QR codes for check-ins.
- **Modify Locations**: Admins can edit or delete existing locations.
- **View Statistics**: Admins can view statistics related to users, locations, and COVID-19 infections.

### User Features

- **Check In**: Users can check in to locations via QR codes.
- **Check Out**: After checking in, users can manually check out or they will be automatically checked out after 3 hours.
- **Report COVID Status**: Users can report positive or negative test results, and notifications will be sent to other users who shared locations.
- **Profile Management**: Users can update personal information such as phone number and email.

### COVID-19 Exposure Tracking

- Users who were in contact with an infected individual are notified via email and the app. They cannot check in until they report a negative test or 14 days have passed.
- Infected users are automatically marked as healthy 14 days after their positive test.

## Testing

This application includes extensive automated testing for the core functionalities. To run the tests:

1. **Run tests**:
   ```bash
   php artisan test
   ```

The test suite covers:

- Positive and negative test reporting.
- Notification handling for infected and contacted users.
- Checking in and out of locations.
- Automated status resets for users after 14 days.
- Prevention of duplicate notifications.

## Technologies Used

- **Laravel**: Backend framework for managing routes, authentication, and business logic.
- **Google Maps API**: Integrated to display and manage location details.
- **MySQL / SQLite**: For database management (configurable in `.env`).
- **Bootstrap**: For responsive UI design.
- **Selenium / PHP Unit**: For testing and automation.

---
