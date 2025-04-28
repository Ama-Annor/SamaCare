<?php
session_start();
require_once '../db/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Assistant - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Chat-specific styles */
        .chat-container {
            height: calc(100vh - 300px);
            min-height: 400px;
            background-color: var(--bg-light);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header .assistant-avatar {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 22px;
        }
        
        .chat-header .assistant-info h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .chat-header .assistant-info p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.bot {
            align-self: flex-start;
            background-color: white;
            border-top-left-radius: 5px;
            color: var(--text-color);
            box-shadow: var(--card-shadow);
        }
        
        .message.user {
            align-self: flex-end;
            background-color: var(--primary-color);
            color: white;
            border-top-right-radius: 5px;
        }
        
        .message .message-time {
            font-size: 11px;
            opacity: 0.7;
            text-align: right;
            margin-top: 5px;
        }
        
        .chat-input-container {
            padding: 15px;
            background-color: white;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .chat-input-container input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 15px;
            outline: none;
            transition: all var(--transition-speed);
        }
        
        .chat-input-container input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }
        
        .chat-input-container button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            transition: all var(--transition-speed);
        }
        
        .chat-input-container button:hover {
            background-color: var(--secondary-color);
        }
        
        .diagnosis-results {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-top: 30px;
        }
        
        .diagnosis-results h3 {
            font-size: 18px;
            color: var(--dark-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .diagnosis-results h3 i {
            color: var(--primary-color);
        }
        
        .diagnosis-list {
            list-style: none;
        }
        
        .diagnosis-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .diagnosis-list li:last-child {
            border-bottom: none;
        }
        
        .probability-indicator {
            height: 8px;
            width: 100px;
            background-color: var(--bg-light);
            border-radius: 4px;
            overflow: hidden;
            margin-left: 15px;
        }
        
        .probability-bar {
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .disclaimer {
            font-size: 13px;
            color: var(--muted-text);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color);
        }
    </style>
</head>
<body>
    <!-- Dashboard Layout -->
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class='bx bx-plus-medical'></i>SAMA<span>CARE</span>
                </div>
                <button class="close-sidebar">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <span>AA</span>
                </div>
                <div class="user-info">
                    <h4>Adwoa Afari</h4>
                    <p>Patient</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.html">
                            <i class='bx bx-home-alt'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="medical_records.html">
                            <i class='bx bx-folder'></i>
                            <span>Medical Records</span>
                        </a>
                    </li>
                    <li>
                        <a href="appointments.html">
                            <i class='bx bx-calendar'></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li>
                        <a href="health_tracking.html">
                            <i class='bx bx-line-chart'></i>
                            <span>Health Tracking</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="health-chat.html">
                            <i class='bx bx-chat'></i>
                            <span>Health Assistant</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="#" class="help-link">
                    <i class='bx bx-help-circle'></i>
                    <span>Help & Support</span>
                </a>
                <a href="../index.html" class="logout-link">
                    <i class='bx bx-log-out'></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class='bx bx-menu'></i>
                    </button>
                    <h1>Health Assistant</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <div class="header-actions">
                        <button class="notification-btn">
                            <i class='bx bx-bell'></i>
                            <span class="notification-badge">2</span>
                        </button>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <div class="user-avatar small">
                                    <span>AA</span>
                                </div>
                                <span class="user-name">Adwoa Afari</span>
                                <i class='bx bx-chevron-down'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Chat Interface -->
                <section class="chat-section">
                    <div class="content-card full-width">
                        <div class="card-header">
                            <h3>Symptom Checker</h3>
                            <a href="#" class="view-all">Health Resources</a>
                        </div>
                        
                        <div class="chat-container">
                            <div class="chat-header">
                                <div class="assistant-avatar">
                                    <i class='bx bx-plus-medical'></i>
                                </div>
                                <div class="assistant-info">
                                    <h3>SamaCare Assistant</h3>
                                    <p>AI-powered symptom checker</p>
                                </div>
                            </div>
                            
                            <div class="chat-messages" id="chat-container">
                                <!-- Messages will be added here dynamically -->
                            </div>
                            
                            <div class="chat-input-container" id="input-container">
                                <input type="text" id="user-input" placeholder="Type your symptoms or answer here..." autocomplete="off">
                                <button onclick="sendResponse()">
                                    <i class='bx bx-send'></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Diagnosis Results -->
                <section>
                    <div class="diagnosis-results">
                        <h3>
                            <i class='bx bx-notepad'></i>
                            Current Possible Diagnoses
                        </h3>
                        <ul class="diagnosis-list" id="predictions-list">
                            <!-- Diagnosis results will be displayed here -->
                        </ul>
                        <div class="disclaimer">
                            <strong>Note:</strong> This is not a medical diagnosis. Always consult with a healthcare professional for proper evaluation and treatment.
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Dashboard Footer -->
            <footer class="dashboard-footer">
                <p>&copy; 2024 SamaCare. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help & Support</a>
                </div>
            </footer>
        </main>
    </div>

    <script>
        const API_URL = "http://localhost:5000";
        let currentSymptoms = [];
        let currentQuestion = null;
        let chatHistory = [];
        let askedSymptoms = []; // Keep track of symptoms we've already asked about

        // Format current time for message timestamp
        function getTimeString() {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' + 
                now.getMinutes().toString().padStart(2, '0');
        }

        // Start the conversation
        window.onload = function() {
            addBotMessage("Welcome to SamaCare Symptom Checker! What's your most noticeable symptom?");
        };

        function addBotMessage(message) {
            const chatContainer = document.getElementById("chat-container");
            const msgElement = document.createElement("div");
            msgElement.className = "message bot";
            
            const messageContent = document.createElement("div");
            messageContent.className = "message-content";
            messageContent.innerText = message;
            
            const messageTime = document.createElement("div");
            messageTime.className = "message-time";
            messageTime.innerText = getTimeString();
            
            msgElement.appendChild(messageContent);
            msgElement.appendChild(messageTime);
            chatContainer.appendChild(msgElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function addUserMessage(message) {
            const chatContainer = document.getElementById("chat-container");
            const msgElement = document.createElement("div");
            msgElement.className = "message user";
            
            const messageContent = document.createElement("div");
            messageContent.className = "message-content";
            messageContent.innerText = message;
            
            const messageTime = document.createElement("div");
            messageTime.className = "message-time";
            messageTime.innerText = getTimeString();
            
            msgElement.appendChild(messageContent);
            msgElement.appendChild(messageTime);
            chatContainer.appendChild(msgElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        async function sendResponse() {
    const userInput = document.getElementById("user-input").value.trim();
    if (!userInput) return;
    
    addUserMessage(userInput);
    document.getElementById("user-input").value = "";
    
    if (!currentQuestion) {
        // First symptom input - send to new endpoint
        showTypingIndicator();
        try {
            const response = await fetch(`${API_URL}/process-symptom`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'initial',
                    current_symptoms: currentSymptoms,
                    input_symptom: userInput
                }),
            });
            
            const data = await response.json();
            hideTypingIndicator();
            
            if (data.valid_symptom) {
                currentQuestion = data.symptom;
                
                addBotMessage(data.message);
            } else if (data.status === 'partial_match') {
                addBotMessage(data.message + " (Type yes to confirm, or try another symptom)");
                // You could enhance this by handling the suggestions
            } else {
                addBotMessage(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            hideTypingIndicator();
            addBotMessage("Sorry, there was an error processing your symptom. Please try again.");
        }
    } else {
        // Responding to a symptom question
        showTypingIndicator();
        try {
            const response = await fetch(`${API_URL}/process-symptom`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'response',
                    current_symptoms: currentSymptoms,
                    response: userInput.toLowerCase(),
                    current_question: currentQuestion,
                    asked_symptoms: askedSymptoms
                }),
            });
            
            const data = await response.json();
            hideTypingIndicator();
            
            if (data.status === 'error') {
                addBotMessage(data.message);
                return;
            }
            
            // First, add the acknowledgment message based on user's response
            if (userInput.toLowerCase() === 'yes') {
                addBotMessage(`✓ Added ${currentQuestion}`);
            } else if (userInput.toLowerCase() === 'no') {
                addBotMessage(`Okay, noted that you don't have ${currentQuestion}.`);
            }
            
            // Small delay before showing the next message
            setTimeout(() => {
                // Update our local state
                currentSymptoms = data.current_symptoms || currentSymptoms;
                askedSymptoms = data.asked_symptoms || askedSymptoms;
                
                // Update predictions if available
                if (data.predictions) {
                    updatePredictions(data.predictions);
                }
                
                // Add response message after a short delay
                addBotMessage(data.message);
                
                // Handle next step based on status
                if (data.status === 'continue') {
                    currentQuestion = data.next_symptom;
                } else if (data.status === 'complete') {
                    currentQuestion = null;
                    addBotMessage("Based on your symptoms, I've listed the possible conditions below. Please consult with a healthcare professional for accurate diagnosis and treatment.");
                }
            }, 700); // 700ms delay for better conversation flow
            
        } catch (error) {
            console.error('Error:', error);
            hideTypingIndicator();
            addBotMessage("Sorry, there was an error processing your response. Please try again.");
        }
    }
}

        function showTypingIndicator() {
            const chatContainer = document.getElementById("chat-container");
            const typingIndicator = document.createElement("div");
            typingIndicator.id = "typing-indicator";
            typingIndicator.className = "message bot";
            typingIndicator.innerHTML = "Typing<span class='dot'>.</span><span class='dot'>.</span><span class='dot'>.</span>";
            chatContainer.appendChild(typingIndicator);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            const typingIndicator = document.getElementById("typing-indicator");
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function updatePredictions(predictions) {
            const predictionsList = document.getElementById("predictions-list");
            predictionsList.innerHTML = "";
            
            predictions.forEach((pred, index) => {
                const li = document.createElement("li");
                const probability = (pred.probability * 100).toFixed(1);
                
                const nameSpan = document.createElement("span");
                nameSpan.innerText = pred.disease;
                
                const rightSide = document.createElement("div");
                rightSide.style.display = "flex";
                rightSide.style.alignItems = "center";
                
                const percentSpan = document.createElement("span");
                percentSpan.innerText = `${probability}%`;
                percentSpan.style.marginRight = "10px";
                
                const indicator = document.createElement("div");
                indicator.className = "probability-indicator";
                
                const bar = document.createElement("div");
                bar.className = "probability-bar";
                bar.style.width = `${probability}%`;
                
                indicator.appendChild(bar);
                rightSide.appendChild(percentSpan);
                rightSide.appendChild(indicator);
                
                li.appendChild(nameSpan);
                li.appendChild(rightSide);
                predictionsList.appendChild(li);
            });
        }

        // Add event listener for Enter key
        document.getElementById("user-input").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                sendResponse();
            }
        });

        // Mobile sidebar toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.add('active');
        });

        document.querySelector('.close-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
        });
    </script>
</body>
</html>