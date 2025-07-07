<?php
session_start();
include 'connect.php'; // Include the database connection script

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Bot</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700&family=Poppins:wght@400;500;600&display=swap');
        body {
            font-family: "Poppins", sans-serif;    
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .chat-container {
            height: 700px;
            width: 600px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background-color: #000000;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        .chat-box {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            border-bottom: 1px solid #ddd;
        }

        .chat-input {
            display: flex;
            padding: 10px;
            background-color: #ffffff;
        }

        .chat-input input {
            flex: 1;
            padding: 5px;
            border: 1px solid #ffffff;
            border-radius: 5px;
        }

        #image-button {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 10px;
            margin-right: 10px;
            width: 40px;
            height: 40px;
            background-color: #ffffff;
            border-radius: 5px;
        }

        #send-button, #mic-button {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 10px;
            width: 40px;
            height: 40px;
            background-color: #000000;
            border-radius: 5px;
        }

        #send-button img, #mic-button img, #image-button img {
            width: 100%;
            height: 100%;
        }

        .message {
            display: flex;
            align-items: flex-start;
            margin: 10px 0;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message.bot {
            flex-direction: row;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border: 2px solid #000000;
            border-radius: 50%;
            margin: 0 10px;
        }

        .text {
            max-width: 70%;
            padding: 10px;
            border-radius: 10px;
            background-color: #ffffff;
        }

        .user .text {
            color: #fff;
            background-color: #000;
            border: 1px solid rgb(255, 255, 255);
        }

        .bot .text {
            background-color: #e1e1e1;
            border: 0.1px solid rgb(255, 255, 255);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            AI - Interaction
        </div>
        <div id="chat-box" class="chat-box"></div>
        <div class="chat-input">
            <div id="image-button" onclick="document.getElementById('image-input').click()">
                <img src="./img/image.jpg" alt="Image">
            </div>
            <input type="text" id="user-input" placeholder="Type a message...">
            <input type="file" id="image-input" accept="image/*" style="display: none;" onchange="handleImageUpload()">
            <div id="send-button" onclick="sendMessage()">
                <img src="./img/send.svg" alt="Send">
            </div>
            <div id="mic-button" onclick="toggleSpeechRecognition()">
                <img src="./img/newmic.svg" alt="Mic">
            </div>
        </div>
    </div>

    <script>
        let selectedImage = null;
        let recognition = null;
        let isListening = false;
        let imageName = '';

        document.addEventListener('DOMContentLoaded', function() {
            fetchChatHistory();
            greetUser();

            const userInput = document.getElementById('user-input');

            // Add event listener for 'keypress' event on input field
            userInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Prevent default behavior (form submission)
                    sendMessage(); // Call your sendMessage function here
                }
            });
        });
        
        // Function to display personalized greeting
        function displayGreeting() {
      // Retrieve user's name from session (PHP session variable)
      var name = "<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest'; ?>";
      
      // Display greeting message
      var greeting = "Hi " + name + " !! How can I help you today?";
      addMessageToChatBox(greeting, 'bot'); // You can use other methods to display this message
    }

    // Call the displayGreeting function when the page loads
    window.onload = function() {
      displayGreeting();
    };

        function handleImageUpload() {
            const imageInput = document.getElementById('image-input');
            const file = imageInput.files[0];
            if (file) {
                imageName = file.name; // Store the image name
                const reader = new FileReader();
                reader.onload = function(e) {
                    selectedImage = e.target.result;
                    displayImagePreview(selectedImage, imageName); // Display image preview and name after successful upload
                };
                reader.readAsDataURL(file);
            }
        }

        function displayImagePreview(imageSrc, imageName) {
            const chatBox = document.getElementById('chat-box');
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', 'user');

            const imgElement = document.createElement('img');
            imgElement.src = 'img/user.svg';
            imgElement.classList.add('avatar');

            const textElement = document.createElement('div');
            textElement.classList.add('text', 'user');
            textElement.innerHTML = `
                <p>Selected Image: ${imageName}</p>
                <img src="${imageSrc}" style="max-width: 100%; border-radius: 10px;">
            `;

            messageElement.appendChild(imgElement);
            messageElement.appendChild(textElement);
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function sendMessage() {
            const apiKey = 'AIzaSyAeXfr9wIsVs-fcdqV90z5HlinSR4cg44I'; // Replace with your API key
            const apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

            const userInput = document.getElementById('user-input');
            const message = userInput.value.trim();

            if (message !== "" || selectedImage) {
                if (message !== "") {
                    addMessageToChatBox(message, 'user');
                    saveMessage('user', message);
                }

                const requestBody = {
                    contents: [
                        {
                            parts: [
                                { text: message }
                            ]
                        }
                    ],
                    generationConfig: {
                        response_mime_type: "application/json"
                    }
                };

                if (selectedImage) {
                    const base64Image = selectedImage.split(',')[1];
                    requestBody.contents[0].parts.push({
                        inlineData: {
                            mimeType: 'image/png',
                            data: base64Image
                        }
                    });
                    selectedImage = null; // Reset the image after sending
                }

                userInput.value = ''; // Clear the input field immediately

                // Display loading indicator (dot)
                addMessageToChatBox('...', 'bot');

                fetch(`${apiUrl}?key=${apiKey}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestBody),
                })
                .then(response => response.json())
                .then(data => {
                    if (data && data.candidates && data.candidates.length > 0) {
                        const botMessage = generateBotResponse(data);
                        updateLastBotMessage(botMessage); // Update the last bot message with the generated response
                        saveMessage('bot', botMessage);
                    } else {
                        // No valid response from bot, display sorry message
                        const errorMessage = "Sorry, I didn't quite get that.";
                        updateLastBotMessage(errorMessage);
                        saveMessage('bot', errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMessage = "Sorry, something went wrong.";
                    updateLastBotMessage(errorMessage);
                    saveMessage('bot', errorMessage);
                })
                .finally(() => {
                    stopSpeechRecognition(); // Stop the speech recognition
                    const micButton = document.getElementById('mic-button');
                    micButton.innerHTML = '<img src="./img/newmic.svg" alt="Mic">'; // Reset mic button icon
                });
            }

            if (message === 'hi' || message === 'hello') {
                const greetingMessage = "Hi 👋, There! How Can I Assist You Today ?";
                addMessageToChatBox(greetingMessage, 'bot');
                userInput.value = ''; // Clear the input field immediately
                saveMessage('bot', greetingMessage);
                stopSpeechRecognition(); // Stop the speech recognition
                const micButton = document.getElementById('mic-button');
                micButton.innerHTML = '<img src="./img/newmic.svg" alt="Mic">'; // Reset mic button icon
            }
        }

        function updateLastBotMessage(message) {
            const chatBox = document.getElementById('chat-box');
            const lastMessage = chatBox.lastChild;

            if (lastMessage && lastMessage.classList.contains('message') && lastMessage.classList.contains('bot')) {
                const textElement = lastMessage.querySelector('.text');
                if (textElement) {
                    textElement.innerHTML = message;
                }
            }
        }

        function generateBotResponse(data) {
            // Extract the generated content from the response data
            let generatedContent = data.candidates[0].content.parts[0].text;

            // Define keywords to filter out
            const keywordsToFilter = ['name', 'essay', 'president']; // Add more keywords as needed

            // Remove keywords from the generated content
            keywordsToFilter.forEach(keyword => {
                const regex = new RegExp('\\b' + keyword + '\\b', 'gi'); // Case-insensitive whole word match
                generatedContent = generatedContent.replace(regex, '');
            });

            // Remove extra symbols using regular expression
            const filteredContent = generatedContent.replace(/[^\w\s]/gi, ''); 

            return filteredContent.trim();
        }

        function addMessageToChatBox(message, sender) {
            const chatBox = document.getElementById('chat-box');
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', sender);

            const imgElement = document.createElement('img');
            imgElement.src = sender === 'user' ? 'img/user.svg' : 'img/bot.svg';
            imgElement.classList.add('avatar');

            const textElement = document.createElement('div');
            textElement.classList.add('text');
            textElement.textContent = message;

            messageElement.appendChild(imgElement);
            messageElement.appendChild(textElement);
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function toggleSpeechRecognition() {
            const micButton = document.getElementById('mic-button');
            if (!isListening) {
                startSpeechRecognition();
                micButton.innerHTML = '<img src="./img/recording.png" alt="Recording" style="width: 20px; height: 20px;">';
            } else {
                stopSpeechRecognition();
                micButton.innerHTML = '<img src="./img/newmic.svg" alt="Mic">';
            }
        }

        function startSpeechRecognition() {
            recognition = new window.webkitSpeechRecognition();
            recognition.lang = 'en-US';
            recognition.continuous = true; // Enable continuous recognition

            recognition.onresult = function(event) {
                const userInput = document.getElementById('user-input');
                userInput.value = event.results[0][0].transcript;
                sendMessage(); // Send the message automatically after voice input
            };

            recognition.start();
            isListening = true;
        }

        function stopSpeechRecognition() {
            if (recognition) {
                recognition.stop();
                isListening = false;
            }
        }

        function saveMessage(sender, message) {
            fetch('save_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sender: sender,
                    message: message
                })
            }).then(response => response.json())
              .then(data => {
                  if (!data.success) {
                      console.error('Failed to save message:', data.error);
                  }
              })
              .catch(error => console.error('Error:', error));
        }

        function fetchChatHistory() {
            fetch('get_chat_history.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages) {
                        data.messages.forEach(msg => {
                            addMessageToChatBox(msg.message, msg.sender);
                        });
                    } else {
                        console.error('Failed to fetch chat history:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
    </script>
</body>
</html> 