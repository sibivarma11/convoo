import React, { useState, useEffect, useRef } from 'react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';
import { motion, AnimatePresence } from 'framer-motion';
import RoomModal from '../components/RoomModal';
import {
    Hash,
    Plus,
    LogOut,
    MessageSquare,
    Send,
    MoreVertical,
    Settings,
    Smile,
    Image as ImageIcon,
    Users,
    Heart,
    ThumbsUp,
    Laugh
} from 'lucide-react';

const Chat = () => {
    const { user, logout } = useAuth();
    const [rooms, setRooms] = useState([]);
    const [selectedRoom, setSelectedRoom] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [onlineUsers, setOnlineUsers] = useState([]);
    const [typingUsers, setTypingUsers] = useState([]);
    const [isRoomModalOpen, setIsRoomModalOpen] = useState(false);
    const messagesEndRef = useRef(null);
    const typingTimeoutRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        fetchRooms();
    }, []);

    useEffect(() => {
        if (selectedRoom) {
            fetchMessages(selectedRoom.id);
            setupEcho(selectedRoom.id);
        }
        return () => {
            if (selectedRoom) {
                window.Echo?.leave(`room.${selectedRoom.id}`);
            }
        };
    }, [selectedRoom]);

    useEffect(() => {
        scrollToBottom();
    }, [messages, typingUsers]);

    const fetchRooms = async () => {
        try {
            const response = await axios.get('/api/rooms');
            setRooms(response.data);
            if (response.data.length > 0 && !selectedRoom) {
                setSelectedRoom(response.data[0]);
            }
        } catch (error) {
            console.error('Failed to fetch rooms:', error);
        }
    };

    const fetchMessages = async (roomId) => {
        try {
            const response = await axios.get(`/api/rooms/${roomId}/messages`);
            setMessages(response.data.data.reverse());
        } catch (error) {
            console.error('Failed to fetch messages:', error);
        }
    };

    const setupEcho = (roomId) => {
        if (window.Echo) {
            window.Echo.join(`room.${roomId}`)
                .here((users) => {
                    setOnlineUsers(users);
                })
                .joining((user) => {
                    setOnlineUsers(prev => [...prev, user]);
                })
                .leaving((user) => {
                    setOnlineUsers(prev => prev.filter(u => u.id !== user.id));
                })
                .listen('.message.sent', (data) => {
                    setMessages(prev => {
                        if (prev.find(m => m.id === data.message.id)) return prev;
                        return [...prev, data.message];
                    });
                })
                .listen('.message.reacted', (data) => {
                    setMessages(prev => prev.map(m =>
                        m.id === data.messageId ? { ...m, reactions: data.reactions } : m
                    ));
                })
                .listenForWhisper('typing', (data) => {
                    setTypingUsers(prev => {
                        if (prev.find(u => u.id === data.user.id)) return prev;
                        return [...prev, data.user];
                    });

                    setTimeout(() => {
                        setTypingUsers(prev => prev.filter(u => u.id !== data.user.id));
                    }, 3000);
                });
        }
    };

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || !selectedRoom) return;

        const tempMessage = {
            id: Date.now(),
            content: newMessage,
            user_id: user.id,
            user: user,
            created_at: new Date().toISOString(),
            isTemp: true
        };

        setMessages(prev => [...prev, tempMessage]);
        setNewMessage('');

        try {
            const response = await axios.post(`/api/rooms/${selectedRoom.id}/messages`, {
                content: newMessage,
                type: 'text'
            });
            setMessages(prev => prev.map(m => m.id === tempMessage.id ? response.data : m));
        } catch (error) {
            setMessages(prev => prev.filter(m => m.id !== tempMessage.id));
            console.error('Failed to send message:', error);
        }
    };

    const handleTyping = () => {
        if (window.Echo && selectedRoom) {
            window.Echo.join(`room.${selectedRoom.id}`).whisper('typing', {
                user: user
            });
        }
    };

    const handleReact = async (messageId, emoji) => {
        try {
            const response = await axios.post(`/api/messages/${messageId}/react`, { emoji });
            setMessages(prev => prev.map(m =>
                m.id === messageId ? { ...m, reactions: response.data.reactions } : m
            ));
        } catch (error) {
            console.error('Failed to react:', error);
        }
    };

    return (
        <div className="h-screen bg-slate-950 flex overflow-hidden">
            <RoomModal
                isOpen={isRoomModalOpen}
                onClose={() => setIsRoomModalOpen(false)}
                onCreated={(newRoom) => {
                    setRooms(prev => [...prev, newRoom]);
                    setSelectedRoom(newRoom);
                }}
            />

            {/* Sidebar */}
            <div className="w-80 bg-slate-900 border-r border-slate-800 flex flex-col">
                <div className="p-6 border-b border-slate-800 flex items-center justify-between">
                    <h1 className="text-xl font-bold text-white font-outfit">Convoo</h1>
                    <button onClick={logout} className="text-slate-400 hover:text-white transition-colors">
                        <LogOut className="w-5 h-5" />
                    </button>
                </div>

                <div className="p-4 flex-1 overflow-y-auto space-y-2 custom-scrollbar">
                    <div className="flex items-center justify-between text-slate-400 px-2 mb-4">
                        <span className="text-xs font-semibold uppercase tracking-wider">Rooms</span>
                        <button
                            onClick={() => setIsRoomModalOpen(true)}
                            className="hover:text-blue-400 transition-colors p-1 hover:bg-slate-800 rounded-lg"
                        >
                            <Plus className="w-4 h-4" />
                        </button>
                    </div>

                    {rooms.map((room) => (
                        <button
                            key={room.id}
                            onClick={() => setSelectedRoom(room)}
                            className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${selectedRoom?.id === room.id
                                    ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20 shadow-lg shadow-blue-500/5'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-slate-300'
                                }`}
                        >
                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${selectedRoom?.id === room.id ? 'bg-blue-500/20' : 'bg-slate-800'
                                }`}>
                                <Hash className="w-5 h-5" />
                            </div>
                            <div className="text-left flex-1 min-w-0">
                                <div className="font-semibold truncate">{room.name}</div>
                                <div className="text-[10px] opacity-60 truncate">{room.description}</div>
                            </div>
                        </button>
                    ))}
                </div>

                {/* User Profile Mini */}
                <div className="p-4 bg-slate-900/50 border-t border-slate-800">
                    <div className="flex items-center gap-3 p-3 rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-emerald-600 flex items-center justify-center text-white font-bold shrink-0">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="text-sm font-semibold text-white truncate">{user.name}</div>
                            <div className="text-xs text-slate-500 truncate">{user.email}</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Chat Area */}
            <div className="flex-1 flex flex-col bg-slate-950 relative">
                {selectedRoom ? (
                    <>
                        {/* Chat Header */}
                        <div className="h-20 bg-slate-950/50 backdrop-blur-xl border-b border-slate-800 px-8 flex items-center justify-between z-10 font-inter">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-blue-400 border border-slate-700">
                                    <Hash className="w-6 h-6" />
                                </div>
                                <div className="min-w-0">
                                    <h2 className="text-lg font-bold text-white tracking-tight truncate">{selectedRoom.name}</h2>
                                    <p className="text-xs text-emerald-400 flex items-center gap-1.5 font-medium">
                                        <span className={`w-1.5 h-1.5 bg-emerald-500 rounded-full ${onlineUsers.length > 0 ? 'animate-pulse' : ''}`}></span>
                                        {onlineUsers.length} Online
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <button className="p-2.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all">
                                    <Users className="w-5 h-5" />
                                </button>
                                <button className="p-2.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all">
                                    <Settings className="w-5 h-5" />
                                </button>
                                <button className="p-2.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all">
                                    <MoreVertical className="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        {/* Messages Area */}
                        <div className="flex-1 overflow-y-auto p-8 space-y-6 custom-scrollbar">
                            {messages.map((msg, i) => (
                                <div key={msg.id} className={`flex items-start gap-4 ${msg.user_id === user.id ? 'flex-row-reverse' : ''}`}>
                                    <div className="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-xs font-bold shrink-0 border border-slate-700">
                                        {msg.user?.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div className={`max-w-[70%] space-y-1 ${msg.user_id === user.id ? 'text-right' : ''}`}>
                                        <div className="flex items-center gap-2 px-1">
                                            <span className="text-sm font-bold text-slate-200">{msg.user?.name}</span>
                                            <span className="text-[10px] text-slate-500 font-medium">{new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                        </div>
                                        <div className="group relative">
                                            <div className={`p-4 rounded-2xl text-sm leading-relaxed shadow-lg ${msg.user_id === user.id
                                                    ? 'bg-blue-600 text-white rounded-tr-none shadow-blue-500/10'
                                                    : 'bg-slate-900 text-slate-200 border border-slate-800 rounded-tl-none'
                                                } ${msg.isTemp ? 'opacity-70' : ''}`}>
                                                {msg.content}
                                            </div>

                                            {/* Reactions Display */}
                                            {msg.reactions && Object.keys(msg.reactions).length > 0 && (
                                                <div className={`flex flex-wrap gap-1 mt-1.5 ${msg.user_id === user.id ? 'justify-end' : ''}`}>
                                                    {Object.entries(msg.reactions).map(([emoji, count]) => (
                                                        <button
                                                            key={emoji}
                                                            onClick={() => handleReact(msg.id, emoji)}
                                                            className="bg-slate-800 border border-slate-700 px-2 py-0.5 rounded-lg text-[10px] flex items-center gap-1 hover:border-blue-500/50 transition-all"
                                                        >
                                                            <span>{emoji}</span>
                                                            <span className="text-slate-400 font-bold">{count}</span>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}

                                            {/* Reaction Picker Hover */}
                                            {!msg.isTemp && (
                                                <div className={`absolute top-0 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1 p-1 bg-slate-800 border border-slate-700 rounded-xl shadow-xl z-20 ${msg.user_id === user.id ? 'right-full mr-2' : 'left-full ml-2'
                                                    }`}>
                                                    {['👍', '❤️', '🔥', '😂', '😮'].map(emoji => (
                                                        <button
                                                            key={emoji}
                                                            onClick={() => handleReact(msg.id, emoji)}
                                                            className="p-1 hover:bg-slate-700 rounded-lg text-sm transition-all grayscale hover:grayscale-0"
                                                        >
                                                            {emoji}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Typing Indicator */}
                            {typingUsers.length > 0 && (
                                <div className="flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center p-2.5">
                                        <div className="flex gap-1">
                                            <span className="w-1 h-1 bg-blue-500 rounded-full animate-bounce"></span>
                                            <span className="w-1 h-1 bg-blue-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                            <span className="w-1 h-1 bg-blue-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                                        </div>
                                    </div>
                                    <span className="text-xs text-slate-500 font-medium italic">
                                        {typingUsers.map(u => u.name).join(', ')} {typingUsers.length === 1 ? 'is' : 'are'} typing...
                                    </span>
                                </div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Message Input */}
                        <div className="p-6 bg-slate-950">
                            <div className="max-w-4xl mx-auto">
                                <form onSubmit={handleSendMessage} className="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-2 flex items-center gap-2 focus-within:border-blue-500/50 transition-all shadow-2xl">
                                    <button type="button" className="p-3 text-slate-500 hover:text-blue-400 hover:bg-slate-800 rounded-2xl transition-all">
                                        <Plus className="w-5 h-5" />
                                    </button>
                                    <input
                                        type="text"
                                        value={newMessage}
                                        onChange={(e) => {
                                            setNewMessage(e.target.value);
                                            handleTyping();
                                        }}
                                        placeholder={`Message #${selectedRoom.name}`}
                                        className="flex-1 bg-transparent border-none outline-none text-white px-2 py-3 placeholder:text-slate-600"
                                    />
                                    <div className="flex items-center gap-1">
                                        <button type="button" className="p-3 text-slate-500 hover:text-blue-400 hover:bg-slate-800 rounded-2xl transition-all">
                                            <Smile className="w-5 h-5" />
                                        </button>
                                        <button type="button" className="p-3 text-slate-500 hover:text-blue-400 hover:bg-slate-800 rounded-2xl transition-all">
                                            <ImageIcon className="w-5 h-5" />
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={!newMessage.trim()}
                                            className="p-3 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-2xl transition-all ml-1 shadow-lg shadow-blue-500/20"
                                        >
                                            <Send className="w-5 h-5" />
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="flex-1 flex flex-col items-center justify-center text-center p-8">
                        <div className="w-24 h-24 bg-slate-900 rounded-3xl flex items-center justify-center mb-6 shadow-2xl border border-slate-800 scale-125">
                            <MessageSquare className="w-10 h-10 text-blue-500" />
                        </div>
                        <h2 className="text-3xl font-bold text-white mb-3 font-outfit">Select a Room</h2>
                        <p className="text-slate-400 max-w-sm font-inter">Choose a room from the sidebar to start connecting with your team and sharing ideas.</p>
                        <div className="mt-8">
                            <button
                                onClick={() => setIsRoomModalOpen(true)}
                                className="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-2xl transition-all flex items-center gap-2 shadow-xl shadow-blue-500/20"
                            >
                                <Plus className="w-5 h-5" /> Create New Room
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default Chat;
