import './bootstrap';
import '../css/app.css';

import React from 'react';
import ReactDOM from 'react-dom/client';
import { AuthProvider, useAuth } from './context/AuthContext';
import Login from './pages/Login';
import Register from './pages/Register';
import Chat from './pages/Chat';
import { Loader2 } from 'lucide-react';

const RootContent = () => {
    const { user, loading } = useAuth();
    const isRegister = window.location.pathname === '/register';

    if (loading) {
        return (
            <div className="min-h-screen bg-slate-950 flex items-center justify-center">
                <Loader2 className="w-12 h-12 text-blue-500 animate-spin" />
            </div>
        );
    }

    if (user) return <Chat />;

    return isRegister ? <Register /> : <Login />;
};

const App = () => {
    return (
        <AuthProvider>
            <RootContent />
        </AuthProvider>
    );
};

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
