# SamaCare Healthcare Application

SamaCare is a comprehensive healthcare application that helps users assess symptoms and manage medical records in one place. It features an intelligent health assessment chatbot for medical guidance and keeps track of your health history, appointments, and records securely.

## Features

- **Intelligent Health Assistant**: AI-powered chatbot that helps assess symptoms and provides preliminary guidance
- **Medical Records Management**: Securely store and access your complete medical history
- **Appointment Scheduling**: Book, reschedule, and receive reminders for medical appointments
- **Health Metrics Tracking**: Monitor vital signs and health metrics over time
- **Secure Data Protection**: All health information is protected with secure encryption and privacy measures

## Getting Started

### Prerequisites

- Python 3.8 or higher
- Flask
- XAMPP (or any MySQL server)
- Web browser

### Installation

1. Clone the repository
git clone https://github.com/Ama-Annor/SamaCare.git
cd SamaCare

2. Set up the database
- Start XAMPP (or your preferred MySQL server)
- Create a new database named `samacare`
- Import the provided SQL file:
  ```
  mysql -u root -p samacare_db < SamaCareDB.sql
  ```

3. Install dependencies
pip install -r requirements.txt

4. Run the Flask application
python app.py

5. Open your web browser and navigate to `http://localhost:5000`

## Usage

1. **Sign Up/Login**: Create an account or login with your credentials
2. **Dashboard**: View your upcoming appointments and health metrics
3. **Health Assistant**: Use the AI chatbot to assess symptoms and get guidance
4. **Appointments**: Schedule appointments with healthcare providers
5. **Medical Records**: Upload and manage your medical records
6. **Health Tracking**: Monitor your health metrics over time

## Project Structure
samacare/
├── app.py                           # Main Flask application entry point
├── db/                              # Database configuration and connection files
│   └── db_connect.php               # Database connection script
├── assets/                          # CSS, JavaScript, and image files
├── diagrams/                        # UML diagrams for the application
├── .ipynb_checkpoints/              # Jupyter notebook checkpoints
├── actions/                         # Action handlers
├── disease_prediction_machine.py    # ML model for disease prediction
├── ghana_disease_symptom_model.py   # Disease-symptom model specific to Ghana
├── chat.html                        # Health assistant chat interface
├── healthchat.html                  # Alternative health chat interface
├── index.html                       # Main landing page
├── model.pkl                        # Serialized machine learning model
├── multilabel_binarizer.pkl         # ML preprocessing tool
├── requirements.txt                 # Project dependencies
└── SamaCareDB.sql                   # SQL database schema and initial data

## Development

The application uses:
- **Backend**: Flask (Python)
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **AI Health Assistant**: Machine learning-based disease prediction system
- **Design Patterns**: Factory, Observer, Strategy, Singleton, Decorator
