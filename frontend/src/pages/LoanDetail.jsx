import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api, { errMsg } from '../api';
import { useAuth } from '../auth';
import { useFetch } from '../hooks';
import { inr, dmy } from '../utils';
import { Badge, State } from '../ui';
import { Decide } from './Loans';

function AddRepayment({ id, onDone }) {
  const [f, setF] = useState({ amount: '', paid_on: new Date().toISOString().slice(0, 10), reference_no: '' }), [err, setErr] = useState('');
  const set = k => e => setF({ ...f, [k]: e.target.value });
  const go = async e => {
    e.preventDefault();
    try { await api.post(`/loans/${id}/repayments`, f); setF({ ...f, amount: '', reference_no: '' }); setErr(''); onDone(); } catch (x) { setErr(errMsg(x)); }
  };
  return (
    <form className="inline" onSubmit={go}>
      <input required type="number" placeholder="Amount (₹)" value={f.amount} onChange={set('amount')} />
      <input required type="date" value={f.paid_on} onChange={set('paid_on')} />
      <input required placeholder="Reference no." value={f.reference_no} onChange={set('reference_no')} />
      <button className="btn">Add repayment</button>{err && <div className="alert">{err}</div>}
    </form>
  );
}

function Upload({ id, onDone }) {
  const [err, setErr] = useState(''), [busy, setBusy] = useState(false);
  const pick = async e => {
    const file = e.target.files[0]; e.target.value = '';
    if (!file) return;
    if (file.type !== 'application/pdf' || file.size > 5 * 1024 * 1024) return setErr('Upload a PDF under 5 MB.');
    setBusy(true); setErr('');
    const fd = new FormData(); fd.append('file', file);
    try { await api.post(`/loans/${id}/documents`, fd); onDone(); } catch (x) { setErr(errMsg(x)); }
    setBusy(false);
  };
  return (
    <div className="inline">
      <label className="btn ghost" style={{ margin: 0, cursor: 'pointer' }}>{busy ? 'Uploading…' : 'Upload PDF'}<input type="file" accept="application/pdf" hidden onChange={pick} disabled={busy} /></label>
      {err && <div className="alert">{err}</div>}
    </div>
  );
}

function Assistant({ id }) {
  const [msgs, setMsgs] = useState([]), [q, setQ] = useState(''), [busy, setBusy] = useState(false);
  const ask = async e => {
    e.preventDefault();
    const question = q.trim(); if (!question) return;
    setMsgs(m => [...m, { who: 'me', text: question }]); setQ(''); setBusy(true);
    try { const { data } = await api.post(`/loans/${id}/ask`, { question }); setMsgs(m => [...m, { who: 'ai', text: data.answer ?? data.data?.answer }]); }
    catch (x) { setMsgs(m => [...m, { who: 'ai', text: errMsg(x), err: true }]); }
    setBusy(false);
  };
  return (
    <section className="card"><h2>Loan assistant</h2>
      <div className="chat">
        {!msgs.length && <div className="chips">{['How much is still outstanding?', 'What is the loan status?', 'How much have I repaid?'].map(t => <button key={t} type="button" className="chip" onClick={() => setQ(t)}>{t}</button>)}</div>}
        {msgs.map((m, i) => <div key={i} className={`msg ${m.who} ${m.err ? 'err' : ''}`}>{m.text}</div>)}
        {busy && <div className="msg ai">Thinking…</div>}
      </div>
      <form className="inline" onSubmit={ask}><input value={q} onChange={e => setQ(e.target.value)} placeholder="Ask about this loan" /><button className="btn" disabled={busy}>Ask</button></form>
    </section>
  );
}

export default function LoanDetail() {
  const { id } = useParams(), { user } = useAuth(), admin = user.role === 'admin';
  const [dlg, setDlg] = useState(null);
  const { data, loading, error, reload } = useFetch(`/loans/${id}`);
  const loan = data?.data ?? data;
  const pending = loan?.documents?.some(d => d.summary_status === 'pending');
  useEffect(() => { if (!pending) return; const t = setInterval(reload, 5000); return () => clearInterval(t); }, [pending, reload]);
  const paid = (loan?.repayments || []).reduce((s, r) => s + Number(r.amount), 0);
  return (
    <State loading={loading && !loan} error={error}>
      {loan && <>
        <Link to="/loans" className="muted">← Back to loans</Link>
        <header className="head"><div className="row"><h1>Loan #{loan.id}</h1><Badge s={loan.status} /></div>
          {admin && loan.status === 'pending' && <div className="row"><button className="btn" onClick={() => setDlg('approved')}>Approve</button><button className="btn danger" onClick={() => setDlg('rejected')}>Reject</button></div>}</header>
        <div className="stats">
          <div><small>Amount</small><b>{inr(loan.amount)}</b></div>
          <div><small>Repaid</small><b>{inr(paid)}</b></div>
          <div><small>Outstanding</small><b>{inr(loan.amount - paid)}</b></div>
          <div><small>Tenure</small><b>{loan.tenure_months} months</b></div>
        </div>
        <section className="card"><h2>Details</h2>
          {admin && loan.user && <p><b>Applicant:</b> {loan.user.name} ({loan.user.email})</p>}
          <p><b>Purpose:</b> {loan.purpose}</p>
          {loan.remarks && <p><b>Remarks:</b> {loan.remarks}</p>}
          <p className="muted">Requested {dmy(loan.created_at)}{loan.approved_at && ` · decided ${dmy(loan.approved_at)}`}</p></section>
        <section className="card"><h2>Repayments</h2>
          {admin && loan.status === 'approved' && <AddRepayment id={id} onDone={reload} />}
          {loan.repayments?.length ? <div className="scroll"><table><thead><tr><th>Date</th><th>Reference</th><th>Amount</th></tr></thead>
            <tbody>{loan.repayments.map(r => <tr key={r.id}><td>{dmy(r.paid_on)}</td><td>{r.reference_no}</td><td className="num">{inr(r.amount)}</td></tr>)}</tbody></table></div>
            : <p className="muted">No repayments recorded yet.</p>}
        </section>
        <section className="card"><h2>Documents</h2>
          {!admin && <Upload id={id} onDone={reload} />}
          {loan.documents?.length ? loan.documents.map(d => {
            const s = typeof d.summary === 'object' ? d.summary : null;
            return <div className="doc" key={d.id}><b>{d.original_name}</b> <span className={`pill ${d.summary_status}`}>{d.summary_status}</span>
              {d.summary_status === 'pending' && <p className="muted">Summary is being generated…</p>}
              {d.summary_status === 'failed' && <p className="muted">We couldn’t summarise this document.</p>}
              {d.summary && <p>{s?.summary ?? d.summary}</p>}
              {s && <p className="muted">{[s.applicant_name, s.employer, s.monthly_income != null && `${inr(s.monthly_income)}/month`].filter(Boolean).join(' · ')}{s.key_risks?.length ? ` — Risks: ${s.key_risks.join('; ')}` : ''}</p>}</div>;
          }) : <p className="muted">No documents uploaded.</p>}
        </section>
        <Assistant id={id} />
        {dlg && <Decide loan={loan} action={dlg} onDone={() => { setDlg(null); reload(); }} onClose={() => setDlg(null)} />}
      </>}
    </State>
  );
}
