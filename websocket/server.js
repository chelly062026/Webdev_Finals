const express = require('express');
const http = require('http');
const WebSocket = require('ws');

const app = express();
app.use(express.json());

const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

wss.on('connection', (ws) => {
  console.log('Client connected');
  
  ws.on('message', (message) => {
    console.log(`Received message: ${message}`);
    
    // Broadcast received message to all other clients
    let parsed;
    try {
      parsed = JSON.parse(message);
    } catch (e) {
      parsed = message.toString();
    }

    const payload = typeof parsed === 'string' ? parsed : JSON.stringify(parsed);
    
    wss.clients.forEach((client) => {
      if (client !== ws && client.readyState === WebSocket.OPEN) {
        client.send(payload);
      }
    });
  });

  ws.on('close', () => {
    console.log('Client disconnected');
  });
});

// Endpoint for Symfony to trigger a broadcast
app.post('/notify', (req, res) => {
  const notification = JSON.stringify(req.body);
  
  let count = 0;
  wss.clients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(notification);
      count++;
    }
  });

  console.log(`Broadcasted notification to ${count} clients.`);
  res.json({ success: true, clientsNotified: count });
});

const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
  console.log(`WebSocket server is running on http://localhost:${PORT}`);
});
