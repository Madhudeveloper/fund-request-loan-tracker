import { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth';
import { errMsg } from '../api';

export default function Auth({ mode }) {
  const reg = mode === 'register';
  const { user, login, register } = useAuth(), nav = useNavigate();
  const [f, setF] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [err, setErr] = useState(''), [busy, setBusy] = useState(false);
  const set = k => e => setF({ ...f, [k]: e.target.value });
  if (user) return <Navigate to="/" replace />;
  const submit = async e => {
    e.preventDefault();
    if (f.password.length < 8) return setErr('Password must be at least 8 characters.');
    if (reg && f.password !== f.password_confirmation) return setErr('Passwords do not match.');
    setBusy(true); setErr('');
    try { reg ? await register(f) : await login(f); nav('/'); } catch (x) { setErr(errMsg(x)); }
    setBusy(false);
  };
  return (
    <div className="auth">
      <div className="auth-hero"><h1>Know where every rupee stands.</h1><p>Request funds, share documents and follow repayments in one place.</p></div>
      <form className="auth-form" onSubmit={submit}>
        <h2>{reg ? 'Create your account' : 'Sign in'}</h2>
        {reg && <label>Full name<input required value={f.name} onChange={set('name')} /></label>}
        <label>Email<input required type="email" value={f.email} onChange={set('email')} /></label>
        <label>Password<input required type="password" value={f.password} onChange={set('password')} /></label>
        {reg && <label>Confirm password<input required type="password" value={f.password_confirmation} onChange={set('password_confirmation')} /></label>}
        {err && <div className="alert">{err}</div>}
        <button className="btn" disabled={busy}>{busy ? 'Please wait…' : reg ? 'Create account' : 'Sign in'}</button>
        <p className="muted">{reg ? <>Have an account? <Link to="/login">Sign in</Link></> : <>New here? <Link to="/register">Create an account</Link></>}</p>
      </form>
    </div>
  );
}
