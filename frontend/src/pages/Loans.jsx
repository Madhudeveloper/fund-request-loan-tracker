import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api, { errMsg } from '../api';
import { useAuth } from '../auth';
import { useFetch, useDebounce } from '../hooks';
import { inr, dmy } from '../utils';
import { Badge, State, Modal } from '../ui';

function NewLoan({ onDone, onClose }) {
  const [f, setF] = useState({ amount: '', tenure_months: '12', purpose: '' });
  const [file, setFile] = useState(null), [err, setErr] = useState(''), [busy, setBusy] = useState(false);
  const set = k => e => setF({ ...f, [k]: e.target.value });
  const submit = async e => {
    e.preventDefault();
    if (f.amount < 10000 || f.amount > 1000000) return setErr('Amount must be between ₹10,000 and ₹10,00,000.');
    if (f.tenure_months < 6 || f.tenure_months > 60) return setErr('Tenure must be 6 to 60 months.');
    if (file && (file.type !== 'application/pdf' || file.size > 5 * 1024 * 1024)) return setErr('Upload a PDF under 5 MB.');
    setBusy(true); setErr('');
    try {
      const { data } = await api.post('/loans', f);
      const id = data.data?.id ?? data.id;
      if (file) { const fd = new FormData(); fd.append('file', file); await api.post(`/loans/${id}/documents`, fd); }
      onDone();
    } catch (x) { setErr(errMsg(x)); setBusy(false); }
  };
  return (
    <Modal title="New loan request" onClose={onClose}>
      <form onSubmit={submit}>
        <label>Amount (₹)<input required type="number" value={f.amount} onChange={set('amount')} /></label>
        <label>Tenure (months)<input required type="number" value={f.tenure_months} onChange={set('tenure_months')} /></label>
        <label>Purpose<input required value={f.purpose} onChange={set('purpose')} /></label>
        <label>Supporting document (PDF)<input type="file" accept="application/pdf" onChange={e => setFile(e.target.files[0])} /></label>
        {err && <div className="alert">{err}</div>}
        <div className="row end"><button type="button" className="btn ghost" onClick={onClose}>Cancel</button><button className="btn" disabled={busy}>{busy ? 'Submitting…' : 'Submit request'}</button></div>
      </form>
    </Modal>
  );
}

export function Decide({ loan, action, onDone, onClose }) {
  const [remarks, setR] = useState(''), [err, setErr] = useState('');
  const go = async e => {
    e.preventDefault();
    try { await api.patch(`/loans/${loan.id}/status`, { status: action, remarks }); onDone(); } catch (x) { setErr(errMsg(x)); }
  };
  return (
    <Modal title={`${action === 'approved' ? 'Approve' : 'Reject'} loan #${loan.id}`} onClose={onClose}>
      <form onSubmit={go}>
        <label>Remarks<textarea required rows="3" value={remarks} onChange={e => setR(e.target.value)} /></label>
        {err && <div className="alert">{err}</div>}
        <div className="row end"><button type="button" className="btn ghost" onClick={onClose}>Cancel</button><button className={`btn ${action === 'rejected' ? 'danger' : ''}`}>{action === 'approved' ? 'Approve loan' : 'Reject loan'}</button></div>
      </form>
    </Modal>
  );
}

export default function Loans() {
  const { user } = useAuth(), admin = user.role === 'admin';
  const [status, setStatus] = useState(''), [q, setQ] = useState(''), [page, setPage] = useState(1), [dlg, setDlg] = useState(null);
  const search = useDebounce(q);
  useEffect(() => setPage(1), [status, search]);
  const { data, loading, error, reload } = useFetch('/loans', { status: status || undefined, search: search || undefined, page });
  const rows = data?.data || [], meta = data?.meta;
  const done = () => { setDlg(null); reload(); };
  return (
    <>
      <header className="head"><h1>{admin ? 'All loans' : 'My loans'}</h1>
        {!admin && <button className="btn" onClick={() => setDlg({ type: 'new' })}>New loan request</button>}</header>
      <div className="filters">
        <input placeholder="Search by purpose" value={q} onChange={e => setQ(e.target.value)} />
        <select value={status} onChange={e => setStatus(e.target.value)}>
          <option value="">All statuses</option>{['pending', 'approved', 'rejected', 'closed'].map(s => <option key={s}>{s}</option>)}
        </select>
      </div>
      <State loading={loading} error={error} empty={!rows.length} msg={admin ? 'No loans match your filters.' : 'You have no loan requests yet. Click “New loan request” to start.'}>
        <div className="scroll"><table>
          <thead><tr><th>#</th>{admin && <th>Applicant</th>}<th>Amount</th><th>Tenure</th><th>Purpose</th><th>Status</th><th>Requested</th><th></th></tr></thead>
          <tbody>{rows.map(l => (
            <tr key={l.id}><td>{l.id}</td>{admin && <td>{l.user?.name}</td>}
              <td className="num">{inr(l.amount)}</td><td>{l.tenure_months} mo</td><td>{l.purpose}</td>
              <td><Badge s={l.status} /></td><td>{dmy(l.created_at)}</td>
              <td className="acts"><Link to={`/loans/${l.id}`}>View</Link>
                {admin && l.status === 'pending' && <>
                  <button className="link ok" onClick={() => setDlg({ loan: l, action: 'approved' })}>Approve</button>
                  <button className="link bad" onClick={() => setDlg({ loan: l, action: 'rejected' })}>Reject</button></>}
              </td></tr>))}</tbody>
        </table></div>
        {meta && <div className="pager"><button className="btn ghost" disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</button>
          <span>Page {meta.current_page} of {meta.last_page}</span>
          <button className="btn ghost" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</button></div>}
      </State>
      {dlg?.type === 'new' && <NewLoan onDone={done} onClose={() => setDlg(null)} />}
      {dlg?.action && <Decide {...dlg} onDone={done} onClose={() => setDlg(null)} />}
    </>
  );
}
