const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const cors = require('cors');

const app = express();
app.use(cors());

const server = http.createServer(app);

// Set up Socket.io and allow your frontend to connect
const io = new Server(server, {
    cors: {
        origin: "*", // In production, you can change "*" to "https://chat-system-web.onrender.com"
        methods: ["GET", "POST"]
    }
});

// This is where the real-time magic happens
io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);

    // When the server receives a message from a user, broadcast it to everyone
    socket.on('send_message', (data) => {
        io.emit('receive_message', data);
    });

    // Handle when a user closes the tab or logs out
    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });
});

// Use Render's dynamically assigned port, or 3000 locally
const PORT = process.env.PORT || 3000;

server.listen(PORT, "0.0.0.0", () => {
    console.log(`Server is running on port ${PORT}`);
});