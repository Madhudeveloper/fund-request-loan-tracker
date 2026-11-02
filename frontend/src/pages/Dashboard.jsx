import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { useFetch } from '../hooks';
import { inr } from '../utils';
import { State } from '../ui';

const COLORS = { pending: '#C98A12', approved: '#1F7A5A', rejected: '#B3402E', closed: '#5B6B7B' };
export default function Dashboard() {
  const { data, loading, error } = useFetch('/dashboard/summary');
  const s = data?.data ?? data;
  const counts = s?.counts ?? s?.by_status ?? {};
  const chart = Object.keys(COLORS).map(k => ({ status: k, loans: counts[k] ?? 0 }));
  return (
    <>
      <header className="head"><h1>Dashboard</h1></header>
      <State loading={loading} error={error}>
        {s && <>
          <div className="stats big">
            <div><small>Total disbursed</small><b>{inr(s.total_disbursed)}</b></div>
            <div><small>Total repaid</small><b>{inr(s.total_repaid)}</b></div>
            <div><small>Outstanding</small><b>{inr(s.total_outstanding)}</b></div>
          </div>
          <section className="card"><h2>Loans by status</h2>
            <div style={{ height: 280 }}><ResponsiveContainer>
              <BarChart data={chart}><XAxis dataKey="status" /><YAxis allowDecimals={false} /><Tooltip />
                <Bar dataKey="loans" radius={[4, 4, 0, 0]}>{chart.map(c => <Cell key={c.status} fill={COLORS[c.status]} />)}</Bar></BarChart>
            </ResponsiveContainer></div></section>
        </>}
      </State>
    </>
  );
}
