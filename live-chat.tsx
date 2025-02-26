"use client"

import type React from "react"
import { useState, useEffect, useRef } from "react"
import { useAuth } from "@/hooks/useAuth"
import AnimatedButton from "./animated-button"
import api from "@/lib/axios"

type Message = {
  id: number
  user: { id: number; name: string }
  content: string
  created_at: string
}

export default function LiveChat() {
  const [messages, setMessages] = useState<Message[]>([])
  const [newMessage, setNewMessage] = useState("")
  const { user } = useAuth()
  const messagesEndRef = useRef<HTMLDivElement>(null)
  const eventSourceRef = useRef<EventSource | null>(null)

  useEffect(() => {
    if (!user) return; // Don't connect if not authenticated

    const connectSSE = () => {
      // Close existing connection if any
      if (eventSourceRef.current) {
        eventSourceRef.current.close()
      }

      // Initialize SSE connection with credentials
      const sseUrl = `${process.env.NEXT_PUBLIC_API_URL}/api/chat/stream`
      eventSourceRef.current = new EventSource(sseUrl, { 
        withCredentials: true 
      })

      // Listen for new messages
      eventSourceRef.current.addEventListener('new-message', (event) => {
        try {
          const data = JSON.parse(event.data)
          const message = data.message // Extract message from the payload
          console.log("New message received:", message)
          setMessages((prevMessages) => [...prevMessages, message])
        } catch (error) {
          console.error('Error parsing message:', error)
        }
      })

      // Handle connection opened
      eventSourceRef.current.onopen = () => {
        console.log('SSE connection established')
      }

      // Handle errors
      eventSourceRef.current.onerror = (error) => {
        console.error('SSE Error:', error)
        if (eventSourceRef.current) {
          eventSourceRef.current.close()
          // Attempt to reconnect after a delay
          setTimeout(connectSSE, 5000)
        }
      }
    }

    fetchMessages()
    connectSSE()

    // Cleanup on unmount
    return () => {
      if (eventSourceRef.current) {
        eventSourceRef.current.close()
      }
    }
  }, [user]) // Depend on user to reconnect when auth state changes

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  const fetchMessages = async () => {
    try {
      const response = await api.get<Message[]>("/api/chat/messages")
      setMessages(response.data)
    } catch (error) {
      console.error("Failed to fetch messages:", error)
    }
  }

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault()
    if (newMessage.trim() && user) {
      try {
        const messageData = {
          content: newMessage
        }

        console.log("Sending message:", messageData)

        const response = await api.post("/api/chat/messages", messageData)
        console.log("Message sent successfully:", response.data)

        setNewMessage("")
      } catch (error) {
        console.error("Error sending message:", error)
      }
    } else {
      console.warn("Message is empty or user is not authenticated.")
    }
  }

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }

  if (!user) {
    return <div className="neon-box p-4">Please log in to access the chat.</div>
  }

  return (
    <div className="neon-box p-4 h-[calc(100vh-200px)] flex flex-col">
      <h2 className="text-xl mb-4">Live Chat</h2>
      <div className="flex-grow overflow-y-auto mb-4">
        {messages.map((message) => (
          <div key={message.id} className="mb-2 flex items-center">
            <span className="text-gray-500 text-xs mr-2">
              {new Date(message.created_at).toLocaleString()}
            </span>
            <span className="font-bold">{message.user.name}: </span>
            <span>{message.content}</span>
          </div>
        ))}
        <div ref={messagesEndRef} />
      </div>
      <form onSubmit={sendMessage} className="flex">
        <input
          type="text"
          value={newMessage}
          onChange={(e) => setNewMessage(e.target.value)}
          className="flex-grow bg-black border border-gray-800 p-2 mr-2"
          placeholder="Wpisz wiadomość..."
        />
        <AnimatedButton type="submit">Wyślij</AnimatedButton>
      </form>
    </div>
  )
} 