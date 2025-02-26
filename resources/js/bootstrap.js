// ...existing code...

// Initialize Pusher and listen for messages
window.Echo.channel('messages')
    .listen('NewMessageEvent', (e) => {
        console.log('Message received:', e.message);
    });

// ...existing code...
