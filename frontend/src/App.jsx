import { Routes, Route, Navigate, NavLink, Outlet } from 'react-router-dom';
import { useAuth } from './auth';
import Auth from './pages/Auth';
import Loans from './pages/Loans';
import LoanDetail from './pages/LoanDetail';
import Dashboard from './pages/Dashboard';

function Shell({ role }) {
  const { user, logout } = useAuth();
  if (!user) return <Navigate to="/login" replace />;
  if (role && user.role !== role) return <Navigate to="/" replace />;
  return (
    <div className="shell">
      <aside className="side">
        <div className="logo">Loan<b>Tracker</b></div>
        <nav>
          {user.role === 'admin' && <NavLink to="/dashboard">Dashboard</NavLink>}
          <NavLink to="/loans">{user.role === 'admin' ? 'All loans' : 'My loans'}</NavLink>
        </nav>
        <div className="me"><span>{user.name}</span><small>{user.role}</small><button className="link" onClick={logout}>Sign out</button></div>
      </aside>
      <main><Outlet /></main>
    </div>
  );
}
const Home = () => { const { user } = useAuth(); return <Navigate to={user?.role === 'admin' ? '/dashboard' : '/loans'} replace />; };

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Auth />} />
      <Route path="/register" element={<Auth mode="register" />} />
      <Route element={<Shell />}>
        <Route path="/" element={<Home />} />
        <Route path="/loans" element={<Loans />} />
        <Route path="/loans/:id" element={<LoanDetail />} />
      </Route>
      <Route element={<Shell role="admin" />}><Route path="/dashboard" element={<Dashboard />} /></Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
