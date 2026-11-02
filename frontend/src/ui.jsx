export const Badge = ({ s }) => <span className={`badge ${s}`}>{s}</span>;
export const State = ({ loading, error, empty, msg, children }) =>
  loading ? <div className="state">Loading…</div> :
  error ? <div className="state err">{error}</div> :
  empty ? <div className="state">{msg || 'Nothing here yet.'}</div> : children;
export const Modal = ({ title, onClose, children }) => (
  <div className="overlay" onClick={onClose}><div className="modal" onClick={e => e.stopPropagation()}><h3>{title}</h3>{children}</div></div>
);
