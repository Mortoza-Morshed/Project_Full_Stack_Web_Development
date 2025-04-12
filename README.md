# Sustainable Energy Usage Tracker (EnergyTrack)

A web application designed to help homeowners track, analyze, and optimize their energy consumption with the help of energy service providers.

## Features

- **User Authentication**: Secure login/registration system with role-based access control
- **Homeowner Features**:
  - Track and monitor energy consumption data
  - View visual reports and analytics
  - Search for energy service providers by location and services
  - Access energy-saving recommendations by category
  - Get personalized tips to reduce energy usage
- **Provider Features**:
  - Create and manage provider profile
  - Add energy-saving recommendations
  - Share expertise with homeowners
- **Admin Features**:
  - Approve/manage user registrations
  - Monitor platform activity
  - System administration

## Technologies Used

- **Frontend**:
  - HTML5
  - TailwindCSS for styling
  - Font Awesome icons
  - Chart.js for data visualization
  - Responsive mobile-first design
- **Backend**:
  - PHP
  - MySQL database
  - Session-based authentication
  - Prepared SQL statements for security

## For AI functionality :-
- Provide GEMINI_API_KEY in get_ai_insights.php
  
  ```
   $api_key = 'YOUR_API_KEY';
  ```

## Project Structure

- `config/`: Database configuration and connection
- `includes/`:
  - `functions.php`: Core utility functions
  - `header.php`: Common header with navigation
- **User Management**:
  - `login.php`: User authentication
  - `register.php`: New user registration
  - `logout.php`: Session termination
- **Homeowner Pages**:
  - `dashboard_homeowner.php`: Main homeowner interface
  - `energy_input.php`: Energy data entry
  - `energy_reports.php`: Usage analytics
  - `provider_search.php`: Provider directory
  - `recommendations.php`: Energy-saving tips
- **Provider Pages**:
  - `dashboard_provider.php`: Provider interface
  - `add_recommendation.php`: Add recommendations
- **Admin Pages**:
  - `admin_dashboard.php`: Administrative controls

## Security Features

- Password hashing using BCRYPT
- Input sanitization
- Prepared SQL statements
- Session-based authentication
- Role-based access control
- XSS prevention

## Database Schema

- **users**: Core user account data
- **homeowners**: Homeowner-specific information
- **providers**: Provider profile data
- **energy_usage**: Consumption records
- **recommendations**: Energy-saving tips

## Setup Instructions

1. Clone repository to web server directory
2. Configure database settings in `config/db_connect.php`
3. Import database schema
4. Access application through web browser
5. Register or use default admin account

## Default Admin Access
- Email: admin@energy.com
- Password: admin123
